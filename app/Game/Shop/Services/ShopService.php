<?php

namespace App\Game\Shop\Services;

use App\Flare\Models\Character;
use App\Game\Shop\Events\SellItemEvent;
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
        $itemsToSell = $character->inventory->slots()->with('item')->get()->filter(function($slot) {
            return !$slot->equipped && $slot->item->type !== 'quest' && $slot->item->type !== 'alchemy';
        });


        if ($itemsToSell->isEmpty()) {
            return 0;
        }

        $trinketCost = 0;

        foreach ($itemsToSell as $slot) {
            if ($slot->item->type === 'trinket') {
                $trinketCost += round($slot->item->gold_dust_cost / 100);
            }
        }

        $cost = 0;

        foreach ($itemsToSell as $slot) {
            $cost += SellItemCalculator::fetchSalePriceWithAffixes($slot->item);
        }

        $ids = $itemsToSell->pluck('id');

        $character->inventory->slots()->whereIn('id', $ids)->delete();

        $cost = $cost + $trinketCost;

        return floor($cost - ($cost * 0.05));
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
