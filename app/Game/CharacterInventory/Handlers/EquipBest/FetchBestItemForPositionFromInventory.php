<?php

namespace App\Game\CharacterInventory\Handlers\EquipBest;

use App\Flare\Models\InventorySlot;
use Illuminate\Support\Collection;

class FetchBestItemForPositionFromInventory {

    private ?Collection $inventorySlots = null;

    private ?Collection $currentlyEquipped = null;

    private InventoryItemComparison $inventoryItemComparison;

    public function __construct(InventoryItemComparison $inventoryItemComparison) {
        $this->inventoryItemComparison = $inventoryItemComparison;
    }

    public function setInventory(?Collection $inventory = null): FetchBestItemForPositionFromInventory {
        $this->inventorySlots = $inventory;

        return $this;
    }

    public function setCurrentlyEquipped(?Collection $currentlyEquipped = null): FetchBestItemForPositionFromInventory {
        $this->currentlyEquipped = $currentlyEquipped;

        return $this;
    }

    public function fetchBestItemForPosition(array $typesForPosition, bool $ignoreMythicsAndUniques = false): ?InventorySlot {

        $hasMythicEquipped = $this->currentlyHasSpecialEquipped('is_mythic');
        $hasUniqueEquipped = $this->currentlyHasSpecialEquipped('is_unique');

        if (!$ignoreMythicsAndUniques && !$hasMythicEquipped) {

            $mythicSlots = $this->fetchSpecialItemFromPossibleItems($typesForPosition, 'is_mythic');

            if (!empty($mythicSlots)) {
                return $this->findBestItem($mythicSlots);
            }
        }

        if (!$ignoreMythicsAndUniques && !$hasUniqueEquipped && !$hasMythicEquipped) {
            $uniqueSlots = $this->fetchSpecialItemFromPossibleItems($typesForPosition, 'is_unique');

            if (!empty($uniqueSlots)) {
                return $this->findBestItem($uniqueSlots);
            }
        }

        $inventorySlots = $this->fetchBestItemsForPositionTypes($typesForPosition);

        if (empty($inventorySlots)) {
            return null;
        }

        return $this->findBestItem($inventorySlots);
    }

    protected function fetchSpecialItemFromPossibleItems(array $typesForPosition, string $attributeKey): array {

        $specialSlotsForType = [];

        if (!$this->currentlyHasSpecialEquipped($attributeKey)) {

            $specialSlotsForType =  $this->inventorySlots
                ->reject(fn($slot) => $this->currentlyHasSpecialEquipped($attributeKey))
                ->filter(function ($slot) use ($typesForPosition, $attributeKey) {
                    return in_array($slot->item->type, $typesForPosition) && $slot->item->{$attributeKey} === true;
                })->all();
        }

        return array_values($specialSlotsForType);
    }

    protected function currentlyHasSpecialEquipped(string $key): bool {
        if (is_null($this->currentlyEquipped)) {
            return false;
        }

        return $this->currentlyEquipped->filter(function($slot) use ($key) {
            return $slot->item->{$key} === true;
        })->isNotEmpty();
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

    protected function fetchBestItemsForPositionTypes(array $typesForPosition): array {

        $bestItems = [];

        if (is_null($this->inventorySlots)) {
            return $bestItems;
        }

        foreach ($typesForPosition as $type) {
            $bestSlot = $this->inventorySlots
                ->filter(function ($slot) use ($type) {
                    return $slot->item->type === $type && !$slot->item->is_unique && !$slot->item->is_mythic;
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
