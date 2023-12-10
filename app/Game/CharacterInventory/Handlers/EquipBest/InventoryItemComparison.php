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

        $badIncrease = 0;

        forEach ($item as $key => $value) {
            if ($key === 'skills') {
                continue;
            }

            if (!is_numeric($value)) {
                continue;
            }

            if ($value >= 0) {
                continue;
            }

            $badIncrease += 1;
        }

        return $badIncrease === 0;
    }
}
