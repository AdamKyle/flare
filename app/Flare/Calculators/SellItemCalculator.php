<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Item;

class SellItemCalculator {

    /**
     * Fetches the item total sale price.
     * 
     * Adds 25% of the item cost to the cost for any additional affixes on the item.
     * 
     * @param Item $item
     * @return int
     */
    public function fetchTotalSalePrice(Item $item): int {
        return round(($item->cost - ($item->cost * 0.25))) + $this->costForAffixes($item);
    }

    protected function costForAffixes(Item $item): int {
        $total = 0;

        if (is_null($item->itemSuffix) && is_null($item->itemPrefix)) {
            $total;
        }

        if (!is_null($item->itemSuffix)) {
            $total += $item->itemSuffix->cost;
        }

        if (!is_null($item->itemPrefix)) {
            $total += $item->itemSuffix->cost;
        }

        return $total;
    }
}