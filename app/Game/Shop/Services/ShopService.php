<?php

namespace App\Game\Shop\Services;

use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Core\Services\EquipItemService;
use App\Game\Shop\Events\BuyItemEvent;
use App\Game\Shop\Events\SellItemEvent;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Http\Request;

class ShopService {

    /**
     * @var EquipItemService $equipItemService
     */
    private EquipItemService $equipItemService;

    /**
     * @param EquipItemService $equipItemService
     */
    public function __construct(EquipItemService $equipItemService) {
        $this->equipItemService = $equipItemService;
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

        $itemsToSell = $character->inventory->slots()->with('item')->get()->filter(function($slot) use($invalidTypes) {
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
