<?php

namespace App\Game\CharacterInventory\Handlers\EquipBest;

use App\Flare\Models\InventorySlot;
use Illuminate\Support\Collection;

class FetchBestItemForPositionFromInventory {

    private ?Collection $inventorySlots = null;

    private InventoryItemComparison $inventoryItemComparison;

    public function __construct(InventoryItemComparison $inventoryItemComparison) {
        $this->inventoryItemComparison = $inventoryItemComparison;
    }

    public function setInventory(?Collection $inventory = null): FetchBestItemForPositionFromInventory {
        $this->inventorySlots = $inventory;

        return $this;
    }

    public function fetchBestItemForPosition(array $typesForPosition, bool $ignoreMythicsAndUniques = false): ?InventorySlot {
        $inventorySlots = $this->fetchBestItemsForPositionTypes($typesForPosition, $ignoreMythicsAndUniques);

        if (empty($inventorySlots)) {
            return null;
        }

        return $this->findBestItem($inventorySlots);
    }

    protected function findBestItem(array $itemSlotsForTypes): ?InventorySlot {
        if (count($itemSlotsForTypes) === 1) {
            return $itemSlotsForTypes[0];
        }

        $bestItemForPosition = null;

        foreach ($itemSlotsForTypes as $index => $bestItem) {
            if (!isset($itemSlotsForTypes[$index + 1])) {
                continue;
            }

            $nextSlot = $itemSlotsForTypes[$index + 1];

            $compareResult = $this->inventoryItemComparison->compareItems($bestItem->item, $nextSlot->item);

            if (is_null($bestItemForPosition) || $compareResult) {
                $bestItemForPosition = $compareResult ? $nextSlot : $bestItem;
            }
        }

        return $bestItemForPosition;
    }

    protected function fetchBestItemsForPositionTypes(array $typesForPosition, bool $ignoreMythicsAndUniques = false): array {

        $bestItems = [];

        if (is_null($this->inventorySlots)) {
            return $bestItems;
        }

        foreach ($typesForPosition as $type) {
            $bestSlot = $this->inventorySlots
                ->filter(function ($slot) use ($type, $ignoreMythicsAndUniques) {

                    if ($ignoreMythicsAndUniques) {
                        return $slot->item->type === $type && !$slot->item->is_unique && !$slot->item->is_mythic;
                    }

                    return $slot->item->type === $type;
                })
                ->reduce(function ($bestItem, $currentItem) {

                    if (is_null($bestItem)) {
                        return $currentItem;
                    }

                    return $this->inventoryItemComparison->compareItems($bestItem->item, $currentItem->item) ? $bestItem : $currentItem;
                });

            if (!is_null($bestSlot)) {
                $bestItems[] = $bestSlot;
            }
        }

        return $bestItems;
    }
}
