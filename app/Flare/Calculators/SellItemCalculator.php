<?php

namespace App\Flare\Calculators;

use App\Flare\Models\Item;
use App\Flare\Traits\IsItemUnique;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use Exception;

class SellItemCalculator
{
    use IsItemUnique;

    const MAX_AFFIX_COST = 2_000_000_000;

    /**
     * Fetches the item total sale price.
     *
     * Minus a 5% tax.
     *
     * @param Item $item
     * @return int
     */
    public function fetchTotalSalePrice(Item $item): int
    {
        if ($item->type === 'trinket') {
            $cost = round(($item->gold_dust_cost / 100));

            return round($cost - $cost * 0.05);
        }

        return intval(floor(($item->cost - ($item->cost * 0.05))));
    }

    /**
     * Fetch the cost of the item with its affixes.
     *
     * @param Item $item
     * @return int
     * @throws Exception
     */
    public function fetchSalePriceWithAffixes(Item $item): int
    {
        $cost = $item->cost;

        if ($cost <= 0 && !is_null($item->specialty_type)) {
            $cost = (new ItemSpecialtyType($item->specialty_type))->getCost();
        }

        if ($cost <= 0) {
            throw new Exception('Cannot determine cost of item for item: ' . $item->affix_name . ' item ID: ' . $item->id);
        }

        if ($this->isItemUnique($item)) {
            return self::MAX_AFFIX_COST - (self::MAX_AFFIX_COST * 0.05);
        }

        if ($this->isItemHoly($item)) {
            return self::MAX_AFFIX_COST  - (self::MAX_AFFIX_COST * 0.05);
        }

        if (! is_null($item->item_suffix_id)) {
            $suffixCost = $item->itemSuffix->cost;

            if ($suffixCost >= self::MAX_AFFIX_COST) {
                $cost += self::MAX_AFFIX_COST;
            } else {
                $cost += $item->itemSuffix->cost;
            }
        }

        if (! is_null($item->item_prefix_id)) {

            $prefixCost = $item->itemPrefix->cost;

            if ($prefixCost >= self::MAX_AFFIX_COST) {
                $cost += self::MAX_AFFIX_COST;
            } else {
                $cost += $item->itemPrefix->cost;
            }
        }

        if ($cost > 2000000000) {
            $cost = 2000000000;
        }

        return floor($cost - ($cost * 0.05));
    }

    /**
     * Fetch min sale price.
     *
     * @param Item $item
     * @return int
     */
    public function fetchMinPrice(Item $item): int
    {
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

        if ($item->is_mythic) {
            $minPrice = RandomAffixDetails::MYTHIC;
        }

        if ($item->is_cosmic) {
            $minPrice = RandomAffixDetails::COSMIC;
        }

        return $minPrice;
    }

    /**
     * Is the item considered unique?
     *
     * - Unique (affixes are randomly generated)
     * - Mythic
     * - Cosmic
     *
     * @param Item $item
     * @return bool
     */
    private function isItemUnique(Item $item): bool
    {

        if ($item->is_mythic) {
            return true;
        }

        if ($item->is_cosmic) {
            return true;
        }

        if (! is_null($item->item_suffix_id)) {
            if ($item->itemSuffix->randomly_generated) {
                return true;
            }
        }

        if (! is_null($item->item_prefix_id)) {
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
    private function isItemHoly(Item $item): bool
    {
        return $item->appliedHolyStacks->count() > 0;
    }

    /**
     * What's the minimum sale price of the unique?
     *
     * @param Item $item
     * @return int
     */
    private function fetchMinimumSalePriceOfUnique(Item $item): int
    {
        $cost = 0;

        if (! is_null($item->item_suffix_id)) {
            if ($item->itemSuffix->randomly_generated) {
                $cost += $item->itemSuffix->cost;
            }
        }

        if (! is_null($item->item_prefix_id)) {
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
