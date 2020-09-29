<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Item;

class SellItemCalculator {

    public function fetchTotalSalePrice(Item $item) {
        return round($item->cost + ($item->cost - ($item->cost * 0.25))) + $this->costForAffixes($item);
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