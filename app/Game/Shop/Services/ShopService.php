<?php

namespace App\Game\Shop\Services;

use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Transformers\ItemTransformer;
use App\Game\CharacterInventory\Services\EquipItemService;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Events\SellItemEvent;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Support\Facades\Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;

class ShopService {

    /**
     * @var EquipItemService $equipItemService
     */
    private EquipItemService $equipItemService;

    /**
     * @var ItemTransformer $itemTransformer
     */
    private ItemTransformer $itemTransformer;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @param EquipItemService $equipItemService
     * @param ItemTransformer $itemTransformer
     * @param Manager $manager
     */
    public function __construct(EquipItemService $equipItemService, ItemTransformer $itemTransformer, Manager $manager) {
        $this->equipItemService = $equipItemService;
        $this->itemTransformer = $itemTransformer;
        $this->manager  = $manager;
    }

    public function getItemsForShop(): array {

        $cachedItems = Cache::get('items-for-shop');

        if (!is_null($cachedItems)) {
            return $cachedItems;
        }

        $items = Item::where('cost', '<=', 2000000000)
            ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
            ->whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->whereNull('specialty_type')
            ->inRandomOrder()
            ->get();

        $items = new Collection($items, $this->itemTransformer);

        $items = $this->manager->createData($items)->toArray();

        Cache::put('items-for-shop', $items);

        return $items;
    }

    /**
     * Sell all the items in the inventory.
     *
     * Sell all that are not equipped and not a quest item.
     *
     * @param Character $character
     * @return int
     */
    public function sellAllItemsInInventory(Character $character): int {
        $invalidTypes = ['alchemy', 'quest', 'trinket'];

        $itemsToSell = $character->inventory->slots()->with('item')->get()->filter(function ($slot) use ($invalidTypes) {
            return !$slot->equipped && !in_array($slot->item->type, $invalidTypes);
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

    /**
     * Buy and replace the item in your inventory.
     *
     * @param Item $item
     * @param Character $character
     * @param array $requestData
     * @return void
     */
    public function buyAndReplace(Item $item, Character $character, array $requestData): void {
        event(new BuyItemEvent($item, $character));

        $character = $character->refresh();

        $inventory = Inventory::where('character_id', $character->id)->first();

        $slot      = InventorySlot::where('equipped', false)->where('item_id', $item->id)->where('inventory_id', $inventory->id)->first();

        $requestData['slot_id'] = $slot->id;

        $this->equipItemService->setRequest($requestData)
            ->setCharacter($character)
            ->replaceItem();

        CharacterAttackTypesCacheBuilder::dispatch($character);
    }

    /**
     * Buy multiple of the same item.
     *
     * @param Character $character
     * @param Item $item
     * @param int $cost
     * @param int $amount
     * @return void
     */
    public function buyMultipleItems(Character $character, Item $item, int $cost, int $amount): void {
        $character->update([
            'gold' => $character->gold - $cost,
        ]);

        $character = $character->refresh();

        for ($i = 1; $i <= $amount; $i++) {
            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $item->id,
            ]);
        }
    }

    /**
     * Sell Item.
     *
     * @param InventorySlot $inventorySlot
     * @param Character $character
     * @return int
     */
    public function sellItem(InventorySlot $inventorySlot, Character $character): int {
        $item         = $inventorySlot->item;
        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($item);

        event(new SellItemEvent($inventorySlot, $character));

        return $totalSoldFor;
    }
}
