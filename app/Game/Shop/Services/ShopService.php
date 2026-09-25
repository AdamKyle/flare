<?php

namespace App\Game\Shop\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Shop\Events\UpdateShopEvent;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ShopService
{
    use ResponseBuilder;

    /**
     * @param EquipItemService $equipItemService
     * @param CharacterInventoryService $characterInventoryService
     * @param CharacterInventoryCountTransformer $characterInventoryCountTransformer
     * @param ItemTransformer $itemTransformer
     * @param Pagination $pagination
     */
    public function __construct(
        private readonly EquipItemService $equipItemService,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
        private readonly ItemTransformer $itemTransformer,
        private readonly Pagination $pagination
    ) {}

    /**
     * Paginate the Shop Items the character's class may buy.
     *
     * @param Character $character
     * @param string|null $type
     * @param string|null $searchText
     * @param string|null $sortCost
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function getItemsForShop(Character $character, ?string $type, ?string $searchText, ?string $sortCost = null, int $perPage = 10, int $page = 1): array
    {
        $items = $this->fetchItemsForShopBasedOnCharacterClass($character, $type, $searchText, $sortCost);

        $result = $this->pagination->buildPaginatedDate($items, $this->itemTransformer, $perPage, $page);

        $result['data'] = array_map(fn (array $item) => $this->withShopItemId($item), $result['data']);

        return $result;
    }

    /**
     * Purchase a single Shop Item for the character.
     *
     * @param Character $character
     * @param Item|null $item
     * @return array
     */
    public function purchaseItem(Character $character, ?Item $item): array
    {
        if ($character->gold === 0) {
            return $this->errorResult($this->notEnoughGoldMessage());
        }

        if (is_null($item)) {
            return $this->errorResult('Item not found.');
        }

        if ($this->costForCharacter($character, $item->cost) > $character->gold) {
            return $this->errorResult($this->notEnoughGoldMessage());
        }

        if ($character->isInventoryFull()) {
            return $this->errorResult($this->inventoryFullMessage());
        }

        $this->completePurchase($character, $item, 1);

        return $this->purchaseSuccessResult($character, 'Purchased: '.$item->affix_name.'.');
    }

    /**
     * Purchase several units of the same Shop Item for the character.
     *
     * @param Character $character
     * @param Item $item
     * @param int $amount
     * @return array
     */
    public function purchaseMultiple(Character $character, Item $item, int $amount): array
    {
        if ($amount > $character->inventory_max || $character->isInventoryFull()) {
            return $this->errorResult('You cannot purchase more then you have inventory space.');
        }

        if ($this->costForCharacter($character, $amount * $item->cost) > $character->gold) {
            return $this->errorResult($this->notEnoughGoldMessage());
        }

        $this->completePurchase($character, $item, $amount);

        return $this->purchaseSuccessResult($character, 'You purchased: '.$amount.' of '.$item->name);
    }

    /**
     * Purchase a Shop Item and equip it in place of one of the character's equipped Items.
     *
     * @param Character $character
     * @param Item $item
     * @param array $requestData
     * @return array
     */
    public function purchaseAndReplace(Character $character, Item $item, array $requestData): array
    {
        $purchaseBlocker = $this->resolveReplacementPurchaseBlocker($character, $item, $requestData);

        if (! is_null($purchaseBlocker)) {
            return $this->errorResult($purchaseBlocker);
        }

        $purchasedSlot = $this->completePurchase($character, $item, 1)->first();

        $equipped = $this->equipPurchasedItem($character, $purchasedSlot, $requestData);

        if (! $equipped) {
            return $this->errorResult($this->purchaseFailedMessage());
        }

        return $this->purchaseSuccessResult($character, 'Purchased and equipped: '.$item->affix_name.'.');
    }

    /**
     * Sell one unequipped inventory Item to the Shop.
     *
     * @param Character $character
     * @param int $slotId
     * @return array
     */
    public function sellSpecificItem(Character $character, int $slotId): array
    {
        $inventorySlot = $character->inventory->slots->first(fn (InventorySlot $slot) => $slot->id === $slotId && ! $slot->equipped);

        if (is_null($inventorySlot)) {
            return $this->errorResult('Item not found.');
        }

        $item = $inventorySlot->item;

        if ($item->type === 'trinket' || $item->type === 'artifact') {
            return $this->errorResult('The shop keeper will not accept this item (Trinkets/Artifacts cannot be sold to the shop).');
        }

        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        event(new SellItemEvent($inventorySlot, $character->refresh()));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return $this->successResult([
            'item_name' => $item->affix_name,
            'sold_for' => $totalSoldFor,
            'message' => 'Sold: '.$item->affix_name.' for: '.number_format($totalSoldFor).' gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
            ],
        ]);
    }

    /**
     * Sell every sellable unequipped inventory Item to the Shop.
     *
     * @param Character $character
     * @return array
     */
    public function sellAllItems(Character $character): array
    {
        $totalSoldFor = $this->sellAllItemsInInventory($character);

        $character->update([
            'gold' => min($character->gold + $totalSoldFor, CurrencyLimit::MAX_GOLD),
        ]);

        $character = $character->refresh();

        $inventory = $this->characterInventoryService->setCharacter($character);

        if ($totalSoldFor === 0) {
            return $this->successResult([
                'message' => 'Could not sell any items ...',
                'inventory' => [
                    'inventory' => $inventory->getInventoryForType('inventory'),
                ],
            ]);
        }

        event(new UpdateTopBarEvent($character));

        return $this->successResult([
            'message' => 'Sold all your items for a total of: '.number_format($totalSoldFor).' gold.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
            ],
        ]);
    }

    /**
     * Sell an inventory slot's Item to the Shop and return the Gold it sold for.
     *
     * @param InventorySlot $inventorySlot
     * @param Character $character
     * @return int
     */
    public function sellItem(InventorySlot $inventorySlot, Character $character): int
    {
        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($inventorySlot->item);

        event(new SellItemEvent($inventorySlot, $character));

        return $totalSoldFor;
    }

    /**
     * Automatically sell a dropped Item the character cannot hold and tell them the new Gold total.
     *
     * @param Character $character
     * @param Item $item
     * @return Character
     */
    public function autoSellItem(Character $character, Item $item): Character
    {
        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        $isGoldCapped = $character->gold + $totalSoldFor > CurrencyLimit::MAX_GOLD;
        $newGold = min($character->gold + $totalSoldFor, CurrencyLimit::MAX_GOLD);

        event(new ServerMessageEvent($character->user, $this->autoSellMessage($item, $totalSoldFor, $newGold, $isGoldCapped)));

        $character->update([
            'gold' => $newGold,
        ]);

        return $character->refresh();
    }

    /**
     * Add the canonical item_id identity the Shop frontend contract requires alongside the existing id.
     *
     * @param array $item
     * @return array
     */
    private function withShopItemId(array $item): array
    {
        return array_merge($item, ['item_id' => $item['id']]);
    }

    /**
     * Apply the Merchant class discount to a Shop cost.
     *
     * @param Character $character
     * @param int $cost
     * @return int
     */
    private function costForCharacter(Character $character, int $cost): int
    {
        if (! $character->classType()->isMerchant()) {
            return $cost;
        }

        return intdiv($cost * 3, 4);
    }

    /**
     * Charge the character for the Items, add them to the inventory and announce the completed purchase.
     *
     * @param Character $character
     * @param Item $item
     * @param int $amount
     * @return EloquentCollection
     */
    private function completePurchase(Character $character, Item $item, int $amount): EloquentCollection
    {
        $cost = $this->costForCharacter($character, $amount * $item->cost);

        $purchasedSlots = DB::transaction(function () use ($character, $item, $amount, $cost): EloquentCollection {
            $character->update([
                'gold' => $character->gold - $cost,
            ]);

            return $character->inventory->slots()->createMany(array_fill(0, $amount, [
                'inventory_id' => $character->inventory->id,
                'item_id' => $item->id,
            ]));
        });

        event(new BuyItemEvent($item, $character->refresh()));

        return $purchasedSlots;
    }

    /**
     * Resolve why the character cannot buy an Item to replace one of their equipped Items.
     *
     * @param Character $character
     * @param Item $item
     * @param array $requestData
     * @return string|null
     */
    private function resolveReplacementPurchaseBlocker(Character $character, Item $item, array $requestData): ?string
    {
        if ($item->craft_only) {
            return 'You are not capable of affording such luxury, child!';
        }

        if ($this->costForCharacter($character, $item->cost) > $character->gold) {
            return $this->notEnoughGoldMessage();
        }

        if ($character->isInventoryFull()) {
            return $this->inventoryFullMessage();
        }

        $replacementExists = $character->inventory->slots()
            ->whereKey($requestData['slot_id'])
            ->where('equipped', true)
            ->exists();

        if (! $replacementExists) {
            return 'The equipped item you chose to replace could not be found.';
        }

        try {
            $this->equipItemService->validateReplacementEligibility($character, $item, $requestData['position']);
        } catch (EquipItemException) {
            return $this->purchaseFailedMessage();
        }

        return null;
    }

    /**
     * Equip the purchased Item in place of the chosen equipped Item and rebuild the character's attack data.
     *
     * @param Character $character
     * @param InventorySlot $purchasedSlot
     * @param array $requestData
     * @return bool
     */
    private function equipPurchasedItem(Character $character, InventorySlot $purchasedSlot, array $requestData): bool
    {
        try {
            $this->equipItemService->setRequest(array_merge($requestData, ['slot_id' => $purchasedSlot->id]))
                ->setCharacter($character)
                ->replaceItem();
        } catch (EquipItemException) {
            return false;
        }

        CharacterAttackTypesCacheBuilder::dispatch($character);

        return true;
    }

    /**
     * Broadcast the character's new Shop totals and build the successful purchase result.
     *
     * @param Character $character
     * @param string $message
     * @return array
     */
    private function purchaseSuccessResult(Character $character, string $message): array
    {
        $character = $character->refresh();

        event(new UpdateShopEvent($character->user, $character->gold, $character->getInventoryCount()));

        return $this->successResult([
            'message' => $message,
            'gold' => $character->gold,
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
        ]);
    }

    /**
     * Remove every sellable unequipped Item from the inventory and return the Gold they sold for after the Shop fee.
     *
     * @param Character $character
     * @return int
     */
    private function sellAllItemsInInventory(Character $character): int
    {
        $itemsToSell = $character->inventory->slots()->with('item')->get()
            ->filter(fn (InventorySlot $slot) => ! $slot->equipped && ! in_array($slot->item->type, ['alchemy', 'quest', 'trinket']));

        if ($itemsToSell->isEmpty()) {
            return 0;
        }

        $totalSalePrice = $itemsToSell->sum(fn (InventorySlot $slot) => SellItemCalculator::fetchSalePriceWithAffixes($slot->item));

        $character->inventory->slots()->whereIn('id', $itemsToSell->pluck('id'))->delete();

        return intdiv($totalSalePrice * 95, 100);
    }

    /**
     * Query the Shop Items for the requested type or, when none is requested, the character's class types.
     *
     * @param Character $character
     * @param string|null $type
     * @param string|null $searchText
     * @param string|null $sortCost
     * @return EloquentCollection
     */
    private function fetchItemsForShopBasedOnCharacterClass(Character $character, ?string $type, ?string $searchText, ?string $sortCost = null): EloquentCollection
    {
        $types = is_null($type) ? ItemTypeMapping::getForClass($character->class->name) : $type;

        $costDirection = $sortCost === 'desc' ? 'desc' : 'asc';

        return Item::where('cost', '<=', 2000000000)
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('specialty_type')
            ->when(! is_null($types), fn (Builder $query) => $query->whereIn('type', Arr::wrap($types)))
            ->when(! is_null($searchText) && $searchText !== '', fn (Builder $query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($searchText).'%']))
            ->orderBy('type', 'desc')
            ->orderBy('cost', $costDirection)
            ->get();
    }

    /**
     * Build the message sent when a dropped Item is automatically sold.
     *
     * @param Item $item
     * @param int $totalSoldFor
     * @param int $newGold
     * @param bool $isGoldCapped
     * @return string
     */
    private function autoSellMessage(Item $item, int $totalSoldFor, int $newGold, bool $isGoldCapped): string
    {
        $soldMessage = 'You are Gold Dust Capped so the item: '.$item->affix_name.' auto sold for: '.number_format($totalSoldFor).' Gold.';

        if ($isGoldCapped) {
            return $soldMessage.' You are now gold capped at: '.number_format($newGold).' Gold. Go spend some of it, or buy Gold Bars for your kingdoms.';
        }

        return $soldMessage.' You now have total amount of gold: '.number_format($newGold).' Gold.';
    }

    /**
     * Describe a purchase the character cannot afford.
     *
     * @return string
     */
    private function notEnoughGoldMessage(): string
    {
        return 'You do not have enough gold.';
    }

    /**
     * Describe a purchase blocked by a full inventory.
     *
     * @return string
     */
    private function inventoryFullMessage(): string
    {
        return 'Inventory is full. Please make room.';
    }

    /**
     * Describe a buy and replace purchase that could not be completed.
     *
     * @return string
     */
    private function purchaseFailedMessage(): string
    {
        return 'Could not complete purchase.';
    }
}
