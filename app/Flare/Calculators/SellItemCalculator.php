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
}
