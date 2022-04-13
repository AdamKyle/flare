<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;

class SellItemCalculator {

    use IsItemUnique;

    /**
     * Fetches the item total sale price.
     *
     * Minus a 5% tax.
     *
     * @param Item $item
     * @return int
     */
    public function fetchTotalSalePrice(Item $item): int {
        if ($item->type === 'trinket') {
            $cost = round(($item->gold_dust_cost / 100));

            return round($cost - $cost * 0.05);
        }

        return floor(($item->cost - ($item->cost * 0.05)));
    }

    /**
     * Fetch the cost of the item with it's affixes.
     *
     * @param Item $item
     * @return int
     */
    public function fetchSalePriceWithAffixes(Item $item): int {
        $cost = $item->cost;

        if ($this->isItemUnique($item)) {
            return $cost;
        }

        if ($this->isItemHoly($item)) {
            return $cost;
        }

        if (!is_null($item->item_suffix_id)) {
            $cost += $item->itemSuffix->cost;
        }

        if (!is_null($item->item_prefix_id)) {
            $cost += $item->itemPrefix->cost;
        }

        return $cost;
    }

    /**
     * Fetch min sale price.
     *
     * @param Item $item
     * @return int
     */
    public function fetchMinPrice(Item $item): int {
        $minPrice = 0;

        if ($this->isItemUnique($item)) {
            $minPrice = $this->fetchMinimumSalePriceOfUnique($item);
        }

        if ($this->isItemHoly($item)) {
            $minPrice += $item->appliedHolyStacks->count() * 1000000000;
        }

        if ($item->type === 'trinket') {
            $minPrice = $item->gold_dust_cost * 100;
        }

        return $minPrice;
    }

    /**
     * Is the item considered unique?
     *
     * @param Item $item
     * @return bool
     */
    protected function isItemUnique(Item $item): bool {
        if (!is_null($item->item_suffix_id)) {
            if ($item->itemSuffix->randomly_generated) {
                return true;
            }
        }

        if (!is_null($item->item_prefix_id)) {
            if ($item->itemPrefix->randomly_generated) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the item considered holy?
     *
     * @param Item $item
     * @return bool
     */
    protected function isItemHoly(Item $item): bool {
        return $item->appliedHolyStacks->count() > 0;
    }

    /**
     * Whats the minimum sale price of the unique?
     *
     * @param Item $item
     * @return int
     */
    protected function fetchMinimumSalePriceOfUnique(Item $item): int {
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
