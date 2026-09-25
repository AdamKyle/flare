<?php

namespace App\Game\Market\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\MarketBoard as MarketBoardModel;
use App\Flare\Models\MarketHistory;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Types\CharacterMessageTypes;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class MarketBoard
{
    use ResponseBuilder;

    /**
     * @param EquipItemService $equipItemService
     * @param ComparisonService $comparisonService
     * @param MarketRealtimePublisher $marketRealtimePublisher
     * @param CharacterInventoryCountTransformer $characterInventoryCountTransformer
     */
    public function __construct(
        private readonly EquipItemService $equipItemService,
        private readonly ComparisonService $comparisonService,
        private readonly MarketRealtimePublisher $marketRealtimePublisher,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
    ) {}

    /**
     * Compare an available listing against the character's equipped Items with its purchase pricing.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @return array
     */
    public function compare(Character $character, MarketBoardModel $listing): array
    {
        if ($listing->character_id === $character->id) {
            return $this->errorResult('You cannot compare your own listing.');
        }

        if ($listing->is_locked) {
            return $this->errorResult($this->listingUnavailableMessage());
        }

        $totalPrice = $this->buyerTotalPrice($listing);
        $comparison = $this->comparisonService->buildShopData($character, $listing->item);

        $comparison['item_to_equip']['cost'] = $totalPrice;

        return $this->successResult(array_merge($comparison, [
            'market_board_id' => $listing->id,
            'listed_price' => $listing->listed_price,
            'total_price' => $totalPrice,
        ]));
    }

    /**
     * Calculate the Gold a buyer pays for a listing including the Market buyer tax.
     *
     * @param MarketBoardModel $listing
     * @return int
     */
    public function buyerTotalPrice(MarketBoardModel $listing): int
    {
        return min(intdiv($listing->listed_price * 105, 100), CurrencyLimit::MAX_GOLD);
    }

    /**
     * Purchase a listing for the character and place the Item in their inventory.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @return array
     */
    public function purchase(Character $character, MarketBoardModel $listing): array
    {
        $reservationError = $this->reserveListing($character, $listing);

        if (! is_null($reservationError)) {
            return $reservationError;
        }

        $price = $this->buyerTotalPrice($listing);
        $purchaseError = $this->resolvePurchaseBlocker($character, $listing, $price);

        if (! is_null($purchaseError)) {
            return $this->releaseReservation($character, $listing, $purchaseError);
        }

        $itemName = $listing->item->affix_name;

        $this->buyItem($character, $listing, $price);

        $listing->delete();

        $this->marketRealtimePublisher->publish($character);

        return $this->purchaseSuccessResult($character, 'Purchased: '.$itemName.'.');
    }

    /**
     * Purchase a listing and equip it in place of one of the character's equipped Items.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @param string $position
     * @param int $replacementSlotId
     * @param string $equipType
     * @return array
     */
    public function purchaseAndReplace(Character $character, MarketBoardModel $listing, string $position, int $replacementSlotId, string $equipType): array
    {
        $reservationError = $this->reserveListing($character, $listing);

        if (! is_null($reservationError)) {
            return $reservationError;
        }

        $price = $this->buyerTotalPrice($listing);
        $purchaseError = $this->resolvePurchaseBlocker($character, $listing, $price)
            ?? $this->resolveReplacementBlocker($character, $listing->item, $position, $replacementSlotId);

        if (! is_null($purchaseError)) {
            return $this->releaseReservation($character, $listing, $purchaseError);
        }

        $itemName = $listing->item->affix_name;

        $slot = $this->buyItem($character, $listing, $price);

        $equipError = $this->equipPurchasedItem($character, $slot, $position, $equipType);

        $listing->delete();

        $this->marketRealtimePublisher->publish($character);

        if (! is_null($equipError)) {
            return $this->purchaseSuccessResult($character, 'Purchased: '.$itemName.'. It could not be equipped: '.$equipError);
        }

        return $this->purchaseSuccessResult($character, 'Purchased and equipped: '.$itemName.'.');
    }

    /**
     * List a Batch Crafting produced item on the Market on the character's behalf.
     *
     * @param Character $character
     * @param Item $item
     * @param int $listingPrice
     * @return void
     */
    public function listBatchCraftedItem(Character $character, Item $item, int $listingPrice): void
    {
        $minimumPrice = SellItemCalculator::fetchMinPrice($item);
        $listPrice = $minimumPrice !== 0 && $minimumPrice > $listingPrice ? $minimumPrice : $listingPrice;
        $listPrice = min($listPrice, CurrencyLimit::MAX_GOLD);

        MarketBoardModel::create([
            'character_id' => $character->id,
            'item_id' => $item->id,
            'listed_price' => $listPrice,
        ]);

        $itemName = $item->affix_name ?? $item->name;

        ServerMessageHandler::sendBasicMessage($character->user, 'Listed: '.$itemName.' For: '.number_format($listPrice).' Gold.');
    }

    /**
     * Atomically reserve an available listing that the character does not own.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @return array|null
     */
    private function reserveListing(Character $character, MarketBoardModel $listing): ?array
    {
        if ($listing->character_id === $character->id) {
            return $this->errorResult('You cannot purchase your own listing.');
        }

        if ($listing->is_locked) {
            return $this->errorResult($this->listingUnavailableMessage());
        }

        $reserved = MarketBoardModel::whereKey($listing->id)->where('is_locked', false)->update(['is_locked' => true]) === 1;

        if (! $reserved) {
            return $this->errorResult($this->listingUnavailableMessage());
        }

        $listing->refresh();

        $this->marketRealtimePublisher->publish($character);

        return null;
    }

    /**
     * Resolve why the character cannot complete the purchase of a reserved listing.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @param int $price
     * @return array|null
     */
    private function resolvePurchaseBlocker(Character $character, MarketBoardModel $listing, int $price): ?array
    {
        if ($character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full. Make some room before buying from the Market.');
        }

        if ($character->gold < $price) {
            return $this->errorResult('You do not have enough Gold. The Market adds a 5% tax to the listed price.');
        }

        $stillReserved = MarketBoardModel::whereKey($listing->id)
            ->where('is_locked', true)
            ->where('character_id', '!=', $character->id)
            ->exists();

        if (! $stillReserved) {
            return $this->errorResult($this->listingUnavailableMessage());
        }

        return null;
    }

    /**
     * Resolve why the purchased Item cannot replace the chosen equipped Item.
     *
     * @param Character $character
     * @param Item $item
     * @param string $position
     * @param int $replacementSlotId
     * @return array|null
     */
    private function resolveReplacementBlocker(Character $character, Item $item, string $position, int $replacementSlotId): ?array
    {
        $replacementExists = $character->inventory->slots()
            ->whereKey($replacementSlotId)
            ->where('equipped', true)
            ->exists();

        if (! $replacementExists) {
            return $this->errorResult('The equipped item you chose to replace could not be found.');
        }

        try {
            $this->equipItemService->validateReplacementEligibility($character, $item, $position);
        } catch (EquipItemException $exception) {
            return $this->errorResult($exception->getMessage());
        }

        return null;
    }

    /**
     * Unlock a reserved listing after a failed purchase and publish its restored availability.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @param array $errorResult
     * @return array
     */
    private function releaseReservation(Character $character, MarketBoardModel $listing, array $errorResult): array
    {
        MarketBoardModel::whereKey($listing->id)->update(['is_locked' => false]);

        $this->marketRealtimePublisher->publish($character);

        return $errorResult;
    }

    /**
     * Charge the buyer, pay the seller, record the sale and give the buyer the Item.
     *
     * @param Character $character
     * @param MarketBoardModel $listing
     * @param int $price
     * @return InventorySlot
     */
    private function buyItem(Character $character, MarketBoardModel $listing, int $price): InventorySlot
    {
        $character->update([
            'gold' => $character->gold - $price,
        ]);

        MarketHistory::create([
            'item_id' => $listing->item_id,
            'sold_for' => $listing->listed_price,
        ]);

        $this->giveGoldToSeller($listing->character, $listing);

        return $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $listing->item_id,
        ]);
    }

    /**
     * Equip the purchased Item in the chosen position and rebuild the character's attack data.
     *
     * @param Character $character
     * @param InventorySlot $slot
     * @param string $position
     * @param string $equipType
     * @return string|null
     */
    private function equipPurchasedItem(Character $character, InventorySlot $slot, string $position, string $equipType): ?string
    {
        try {
            $this->equipItemService->setRequest([
                'slot_id' => $slot->id,
                'position' => $position,
                'equip_type' => $equipType,
            ])->setCharacter($character->refresh())->replaceItem();
        } catch (EquipItemException $exception) {
            return $exception->getMessage();
        }

        CharacterAttackTypesCacheBuilder::dispatch($character->refresh())->delay(now()->addSeconds(2));

        return null;
    }

    /**
     * Give the seller the listed price minus the Market seller tax.
     *
     * @param Character $listingCharacter
     * @param MarketBoardModel $listing
     * @return void
     */
    private function giveGoldToSeller(Character $listingCharacter, MarketBoardModel $listing): void
    {
        $gold = intdiv($listing->listed_price * 95, 100);

        $listingCharacter->update([
            'gold' => min($gold + $listingCharacter->gold, CurrencyLimit::MAX_GOLD),
        ]);

        $message = 'Sold market listing: '.$listing->item->affix_name.' for: '.number_format($gold).' After fees (5% tax). You now have: '.number_format($listingCharacter->gold);

        ServerMessageHandler::handleMessage($listingCharacter->user, CharacterMessageTypes::SOLD_ITEM_ON_MARKET, $message);

        event(new UpdateCharacterBaseDetailsEvent($listingCharacter->refresh()));
    }

    /**
     * Build the successful purchase result with the buyer's refreshed Gold and inventory count.
     *
     * @param Character $character
     * @param string $message
     * @return array
     */
    private function purchaseSuccessResult(Character $character, string $message): array
    {
        $character = $character->refresh();

        return $this->successResult([
            'message' => $message,
            'gold' => $character->gold,
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
        ]);
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
