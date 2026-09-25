<?php

namespace App\Game\Market\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\MarketBoard as MarketBoardModel;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Market\Enums\MarketListingPriceSort;
use App\Game\Market\Transformers\MarketItemsTransformer;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item as FractalItem;

class MarketListingService
{
    use ResponseBuilder;

    /**
     * @param Manager $manager
     * @param MarketItemsTransformer $marketItemsTransformer
     * @param MarketRealtimePublisher $marketRealtimePublisher
     * @param CharacterInventoryService $characterInventoryService
     * @param CharacterInventoryCountTransformer $characterInventoryCountTransformer
     * @param AutomationRestrictionService $automationRestrictionService
     */
    public function __construct(
        private readonly Manager $manager,
        private readonly MarketItemsTransformer $marketItemsTransformer,
        private readonly MarketRealtimePublisher $marketRealtimePublisher,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
        private readonly AutomationRestrictionService $automationRestrictionService,
    ) {}

    /**
     * Paginate the unlocked Market listings matching the browse filters.
     *
     * @param string $searchText
     * @param string|null $type
     * @param MarketListingPriceSort|null $priceSort
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function browse(string $searchText, ?string $type, ?MarketListingPriceSort $priceSort, int $perPage, int $page): array
    {
        $query = $this->listingQuery()
            ->where('is_locked', false)
            ->when(! is_null($type), function (Builder $listingQuery) use ($type) {
                $listingQuery->whereHas('item', fn (Builder $itemQuery) => $itemQuery->where('type', $type));
            })
            ->when($searchText !== '', function (Builder $listingQuery) use ($searchText) {
                $listingQuery->whereHas('item', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', '%'.$searchText.'%'));
            });

        if (! is_null($priceSort)) {
            $query->orderBy('listed_price', $priceSort->value);
        }

        $query->orderByDesc('created_at')->orderByDesc('id');

        return $this->transformPaginator($query->paginate($perPage, ['*'], 'page', $page));
    }

    /**
     * Paginate every listing owned by the character, including listings locked for editing.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function ownedListings(Character $character, int $perPage, int $page): array
    {
        $paginator = $this->listingQuery()
            ->where('character_id', $character->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->transformPaginator($paginator);
    }

    /**
     * Return the current details of a listing when it is available to the character.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @return array
     */
    public function listingDetails(Character $character, MarketBoardModel $listing): array
    {
        if ($listing->is_locked && $listing->character_id !== $character->id) {
            return $this->errorResult($this->listingUnavailableMessage());
        }

        return $this->successResult([
            'listing' => $this->transformListing($listing),
        ]);
    }

    /**
     * List an owned, market sellable inventory Item on the Market for the character.
     *
     * @param Character $character
     * @param int $slotId
     * @param int $listFor
     * @return array
     */
    public function listItem(Character $character, int $slotId, int $listFor): array
    {
        $restriction = $this->inventoryRestriction($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $slot = $character->inventory->slots()->find($slotId);

        if (is_null($slot)) {
            return $this->errorResult('The item you want to list could not be found in your inventory.');
        }

        $listingBlocker = $this->resolveListingBlocker($slot, $listFor);

        if (! is_null($listingBlocker)) {
            return $listingBlocker;
        }

        $listPrice = min($listFor, CurrencyLimit::MAX_GOLD);
        $itemName = $slot->item->affix_name;

        MarketBoardModel::create([
            'character_id' => $character->id,
            'item_id' => $slot->item_id,
            'listed_price' => $listPrice,
        ]);

        $slot->delete();

        $this->marketRealtimePublisher->publish($character);

        $inventory = $this->characterInventoryService->setCharacter($character->refresh());

        return $this->successResult([
            'message' => 'Listed: '.$itemName.' For: '.number_format($listPrice).' Gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
                'usable_items' => $inventory->getInventoryForType('usable_items'),
            ],
        ]);
    }

    /**
     * Lock an owned listing so its price can be edited without other characters buying it.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @return array
     */
    public function beginEdit(Character $character, MarketBoardModel $listing): array
    {
        if ($listing->character_id !== $character->id) {
            return $this->errorResult($this->notOwnerMessage());
        }

        $locked = MarketBoardModel::whereKey($listing->id)->where('is_locked', false)->update(['is_locked' => true]) === 1;

        if (! $locked) {
            return $this->errorResult('This listing is already locked. It may be in the middle of a sale or another edit.');
        }

        $this->marketRealtimePublisher->publish($character);

        return $this->successResult([
            'listing' => $this->transformListing($listing->refresh()),
        ]);
    }

    /**
     * Save a new price for an owned listing and make it available again.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @param int $listedPrice
     * @return array
     */
    public function updatePrice(Character $character, MarketBoardModel $listing, int $listedPrice): array
    {
        if ($listing->character_id !== $character->id) {
            return $this->errorResult($this->notOwnerMessage());
        }

        $listing->update([
            'listed_price' => min($listedPrice, CurrencyLimit::MAX_GOLD),
            'is_locked' => false,
        ]);

        $this->marketRealtimePublisher->publish($character);

        return $this->successResult([
            'message' => 'Listing for: '.$listing->item->affix_name.' updated.',
            'listing' => $this->transformListing($listing->refresh()),
        ]);
    }

    /**
     * Release the edit lock on an owned listing without changing its price.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @return array
     */
    public function cancelEdit(Character $character, MarketBoardModel $listing): array
    {
        if ($listing->character_id !== $character->id) {
            return $this->errorResult($this->notOwnerMessage());
        }

        $listing->update(['is_locked' => false]);

        $this->marketRealtimePublisher->publish($character);

        return $this->successResult([
            'message' => 'Stopped editing: '.$listing->item->affix_name.'.',
        ]);
    }

    /**
     * Remove an owned listing from the Market and return its Item to the character's inventory.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @return array
     */
    public function delist(Character $character, MarketBoardModel $listing): array
    {
        if ($listing->character_id !== $character->id) {
            return $this->errorResult($this->notOwnerMessage());
        }

        $restriction = $this->inventoryRestriction($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if ($character->isInventoryFull()) {
            return $this->errorResult('You do not have the inventory space to delist this item.');
        }

        $itemName = $listing->item->affix_name;

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $listing->item_id,
        ]);

        $listing->delete();

        $this->marketRealtimePublisher->publish($character);

        $character = $character->refresh();

        return $this->successResult([
            'message' => 'Delisted: '.$itemName.'.',
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
        ]);
    }

    /**
     * Build the base listing query with the relationships the listing transformer needs.
     *
     * @return Builder
     */
    private function listingQuery(): Builder
    {
        return MarketBoardModel::query()->with(['item.itemPrefix', 'item.itemSuffix', 'character']);
    }

    /**
     * Transform a page of listings with their canonical item details into the paginated API shape.
     *
     * @param LengthAwarePaginator $paginator
     * @return array
     */
    private function transformPaginator(LengthAwarePaginator $paginator): array
    {
        $resource = new Collection($paginator->items(), $this->marketItemsTransformer);

        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $data = $this->manager->parseIncludes('item')->createData($resource)->toArray();

        $data['meta']['can_load_more'] = $paginator->hasMorePages();

        return $data;
    }

    /**
     * Transform one listing with its canonical item details.
     *
     * @param MarketBoardModel $listing
     * @return array
     */
    private function transformListing(MarketBoardModel $listing): array
    {
        $resource = new FractalItem($listing, $this->marketItemsTransformer);

        return $this->manager->parseIncludes('item')->createData($resource)->toArray()['data'];
    }

    /**
     * Resolve why an inventory slot cannot be listed at the requested price.
     *
     * @param InventorySlot $slot
     * @param int $listFor
     * @return array|null
     */
    private function resolveListingBlocker(InventorySlot $slot, int $listFor): ?array
    {
        if (! $slot->item->market_sellable) {
            return $this->errorResult('This item cannot be sold on the Market.');
        }

        $minimumPrice = SellItemCalculator::fetchMinPrice($slot->item);

        if ($minimumPrice !== 0 && $minimumPrice > $listFor) {
            return $this->errorResult('No! The minimum selling price is: '.number_format($minimumPrice).' Gold.');
        }

        return null;
    }

    /**
     * Resolve the automation restriction that blocks inventory management for the character.
     *
     * @param Character $character
     * @return array|null
     */
    private function inventoryRestriction(Character $character): ?array
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (is_null($restriction)) {
            return null;
        }

        return $this->errorResult($restriction['message']);
    }

    /**
     * Describe why a character cannot manage a listing they do not own.
     *
     * @return string
     */
    private function notOwnerMessage(): string
    {
        return 'You do not own this listing.';
    }

    /**
     * Describe a listing that is no longer available to purchase.
     *
     * @return string
     */
    private function listingUnavailableMessage(): string
    {
        return 'That listing is no longer available. It may have been sold, delisted or is being edited.';
    }
}
