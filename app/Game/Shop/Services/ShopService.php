<?php

namespace App\Game\Shop\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Pagination\Pagination;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Exceptions\EquipItemException;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Shop\Transformers\ShopTransformer;
use Exception;
use Facades\App\Flare\Calculators\SellItemCalculator;
use League\Fractal\Manager;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ShopService
{
    use ResponseBuilder;

    /**
     * @param EquipItemService $equipItemService
     * @param CharacterInventoryService $characterInventoryService
     * @param Pagination $pagination
     * @param ShopTransformer $shopTransformer
     * @param Manager $manager
     */
    public function __construct(private readonly EquipItemService $equipItemService,
                                private readonly CharacterInventoryService $characterInventoryService,
                                private readonly Pagination $pagination,
                                private readonly ShopTransformer $shopTransformer,
                                private readonly Manager $manager
    ){}

    public function getItemsForShop(Character $character, int $perPage, int $page, ?string $searchText, ?array $filters): array {
        $items = $this->fetchItemsForShopBasedOnCharacterClass($character, $searchText, $filters);

        return $this->pagination->buildPaginatedDate($items, $this->shopTransformer, $perPage, $page);
    }

    public function sellAllItems(Character $character): array
    {

        $totalSoldFor = $this->sellAllItemsInInventory($character);

        $newGold = $character->gold + $totalSoldFor;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newGold,
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
     * Buy and replace the item in your inventory.
     *
     * @throws EquipItemException
     */
    public function buyAndReplace(Item $item, Character $character, array $requestData): void
    {
        event(new BuyItemEvent($item, $character));

        $character = $character->refresh();

        $inventory = Inventory::where('character_id', $character->id)->first();

        $slot = InventorySlot::where('equipped', false)->where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        $requestData['slot_id'] = $slot->id;

        $this->equipItemService->setRequest($requestData)
            ->setCharacter($character)
            ->replaceItem();

        CharacterAttackTypesCacheBuilder::dispatch($character);
    }

    /**
     * Buy multiple of the same item.
     */
    public function buyMultipleItems(Character $character, Item $item, int $cost, int $amount): void
    {
        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        $character = $character->refresh();

        for ($i = 1; $i <= $amount; $i++) {
            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $item->id,
            ]);
        }
    }

    /**
     * Sell Item.
     */
    public function sellItem(InventorySlot $inventorySlot, Character $character): int
    {
        $item = $inventorySlot->item;
        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        event(new SellItemEvent($inventorySlot, $character));

        return $totalSoldFor;
    }

    /**
     * @param Character $character
     * @param Item $item
     * @return Character
     * @throws Exception
     */
    public function autoSellItem(Character $character, Item $item): Character {
        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        $newGold = $character->gold + $totalSoldFor;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;

            event(new ServerMessageEvent($character->user, 'You are Gold Dust Capped so the item: ' . $item->affix_name . ' auto sold for: ' . number_format($totalSoldFor) . ' Gold. You are now gold capped at: ' . number_format($newGold) . ' Gold. Go spend some of it, or buy Gold Bars for your kingdoms.'));
        } else {
            event(new ServerMessageEvent($character->user, 'You are Gold Dust Capped so the item: ' . $item->affix_name . ' auto sold for: ' . number_format($totalSoldFor) . ' Gold. You now have total amount of gold: ' . number_format($newGold) . ' Gold.'));
        }

        $character->update([
            'gold' => $newGold,
        ]);

        return $character->refresh();
    }

    /**
     * Sell all the items in the inventory.
     *
     * Sell all that are not equipped and not a quest item.
     */
    private function sellAllItemsInInventory(Character $character): int
    {
        $invalidTypes = ['alchemy', 'quest', 'trinket'];

        $itemsToSell = $character->inventory->slots()->with('item')->get()->filter(function ($slot) use ($invalidTypes) {
            return ! $slot->equipped && ! in_array($slot->item->type, $invalidTypes);
        });

        if ($itemsToSell->isEmpty()) {
            return 0;
        }

        $cost = 0;

        foreach ($itemsToSell as $slot) {
            $cost += SellItemCalculator::fetchSalePriceWithAffixes($slot->item);
        }

        $ids = $itemsToSell->pluck('id');

        $character->inventory->slots()->whereIn('id', $ids)->delete();

        return floor($cost - ($cost * 0.05));
    }

    private function fetchItemsForShopBasedOnCharacterClass(Character $character, ?string $searchText, ?array $filters): EloquentCollection {
        $className = $character->class->name;

        $types = !isset($filters['type']) ? ItemTypeMapping::getForClass($className) : $filters['type'];
        $costOrder = !isset($filters['sort_cost']) ? 'asc' : $filters['sort_cost'];

        $items = Item::where('cost', '<=', 2000000000)
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('specialty_type');

        if (!is_null($types)) {
            if (is_array($types)) {
                $items = $items->whereIn('type', $types);
            } else {
                $items = $items->where('type', $types);
            }
        }

        if (!is_null($searchText)) {
            $items = $items->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchText) . '%']);
        }

        return $items
            ->orderBy('type', 'desc')
            ->orderBy('cost', $costOrder)
            ->get();
    }

}
