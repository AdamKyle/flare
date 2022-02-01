<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Item;

class SellItemCalculator {

    /**
     * Fetches the item total sale price.
     *
     * Minus a 5% tax.
     *
     * @param Item $item
     * @return int
     */
    public function fetchTotalSalePrice(Item $item): int {
        return round(($item->cost - ($item->cost * 0.05)));
    }

    /**
     * Fetch the cost of the item with it's affixes.
     *
     * @param Item $item
     * @return int
     */
    public function fetchSalePriceWithAffixes(Item $item): int {
        $cost = $item->cost;

        if (!is_null($item->item_suffix_id)) {
            $cost += $item->itemSuffix->cost;
        }

        if (!is_null($item->item_prefix_id)) {
            $cost += $item->itemPrefix->cost;
        }

        return $cost;
    }

    public function fetchMinimumSalePriceOfUnique(Item $item): int {
        $cost = 0;

        if (!is_null($item->item_suffix_id)) {
            if ($item->itemSuffix->randomly_generated) {
                $cost += $item->itemSuffix->cost;
            }
        }

        if (!is_null($item->item_prefix_id)) {
            if ($item->itemPrefix->randomly_generated) {
                $cost += $item->itemPrefix->cost;
            }
        }

        if ($cost === 0) {
            return $cost;
        }

        return (int) floor($cost / 2);
    }
}
