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
            return !$slot->equipped && $slot->item->type !== 'quest' &&
                (is_null($slot->item->itemPrefix) && is_null($slot->item->itemSuffix)) &&
                $slot->item->type !== 'alchemy';
        });

        if ($itemsToSell->isEmpty()) {
            return 0;
        }

        $cost = $itemsToSell->sum('item.cost');

        $ids = $itemsToSell->pluck('id');

        $character->inventory->slots()->whereIn('id', $ids)->delete();

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
