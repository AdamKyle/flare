<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Game\Core\Events\SellItemEvent;
use Facades\App\Flare\Calculators\SellItemCalculator;
use Illuminate\Database\Eloquent\Collection;

class ShopService {

    /**
     * Sell all the items in the inventory.
     * 
     * Sell all that are not equipped and not a quest item.
     * 
     * @param Character $character
     * @return int
     */
    public function sellAllItemsInInventory(Character $character): int {
        $itemsToSell = $character->inventory->slots->filter(function($slot) {
            return !$slot->equipped && $slot->item->type !== 'quest';
        })->all();

        $itemsToSell = collect($itemsToSell);

        if ($itemsToSell->isEmpty()) {
            return 0;
        }

        $totalSoldFor = 0;

        foreach ($itemsToSell as $itemSlot) {
            $totalSoldFor += SellItemCalculator::fetchTotalSalePrice($itemSlot->item);

            event(new SellItemEvent($itemSlot, $character));
        }

        return $totalSoldFor;
    }

    /**
     * Fetch the total sold for amount.
     * 
     * @param Collection $inventorySlots
     * @param Character $character
     * @return int
     */
    public function fetchTotalSoldFor(Collection $inventorySlots, Character $character): int {
        $totalSoldFor = 0;

        foreach ($inventorySlots as $slot) {
            $character = $character->refresh();

            event(new SellItemEvent($slot, $character));

            $totalSoldFor += SellItemCalculator::fetchTotalSalePrice($slot->item);
        }

        return $totalSoldFor;
    }
}