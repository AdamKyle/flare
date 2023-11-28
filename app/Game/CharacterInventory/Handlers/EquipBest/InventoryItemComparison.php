<?php

namespace App\Game\CharacterInventory\Handlers\EquipBest;

use App\Flare\Models\Item;
use App\Game\Core\Comparison\ItemComparison;

class InventoryItemComparison {

    private ItemComparison $itemComparison;

    public function __construct(ItemComparison $itemComparison) {

        $this->itemComparison = $itemComparison;
    }

    public function compareItems(Item $itemOne, Item $itemTwo) {
        $result = $this->itemComparison->fetchItemComparisonDetails($itemTwo, $itemOne);

        return $this->isItemGood($result);
    }

    private function isItemGood(array $item): bool {

        $goodIncrease = 0;
        $badIncrease = 0;

        forEach ($item as $key => $value) {
            if ($key === 'skills') {
                continue;
            }

            if (!is_numeric($value)) {
                continue;
            }

            if ($value >= 0) {
                $goodIncrease += 1;

                continue;
            }

            $badIncrease += 1;
        }

        if ($goodIncrease >= $badIncrease) {
            return true;
        }

        return false;
    }
}
