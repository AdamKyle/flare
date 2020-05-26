<?php

namespace App\Game\Core\Comparison;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Core\Exceptions\EquipItemException;
use Illuminate\Database\Eloquent\Collection;

class ItemComparison {

    public function fetchDetails(Item $toCompare, Collection $inventorySlots): array {
        $comparison = [];

        foreach($inventorySlots as $slot) {
            if ($slot->position !== null) {
                $comparison[$slot->position] = $this->fetchHandComparison($toCompare, $inventorySlots, $slot->position);
            }
        }

        return $comparison;
    }

    protected function fetchHandComparison(Item $toCompare, Collection $inventorySlots, string $hand): array {

        $foundPosition = $inventorySlots->filter(function($slot) use ($hand) {
            return $slot->position === $hand;
        })->first();
        
        if ($this->isItemBetter($toCompare, $foundPosition->item)) {
            return [
                'is_better'          => true,
                'replaces_item'      => $foundPosition->item,
                'slot'               => $foundPosition,
                'position'           => $foundPosition->position,
                'damage_adjustment'  => $this->getDamageIncrease($toCompare, $foundPosition->item),
                'ac_adjustment'      => $this->getAcIncrease($toCompare, $foundPosition->item),
                'healing_adjustment' => $this->getHealIncrease($toCompare, $foundPosition->item)
            ];
        } else {
            return [
                'is_better'          => false,
                'replaces_item'      => null,
                'slot'               => $foundPosition,
                'position'           => $foundPosition->position,
                'damage_adjustment'  => $this->getDamageDecrease($toCompare, $foundPosition->item),
                'ac_adjustment'      => $this->getAcDecrease($toCompare, $foundPosition->item),
                'healing_adjustment' => $this->getHealDecrease($toCompare, $foundPosition->item)
            ];
        }
    }

    public function getDamageIncrease(Item $toCompare, Item $equipped): int {
        $totalDamageForEquipped = $equipped->getTotalDamage();
        $totalDamageForCompare  = $toCompare->getTotalDamage();

        return $totalDamageForCompare - $totalDamageForEquipped;
    }

    public function getDamageDecrease(Item $toCompare, Item $equipped): int {
        $totalDamageForEquipped = $equipped->getTotalDamage();
        $totalDamageForCompare  = $toCompare->getTotalDamage();

        if ($totalDamageForCompare < $totalDamageForEquipped) {
            return $totalDamageForCompare - $totalDamageForEquipped;
        }

        return 0;
    }

    public function getAcIncrease(Item $toCompare, Item $equipped): int {
        $totalDefenceForEquipped = $equipped->getTotalDefence();
        $totalDefenceForCompare  = $toCompare->getTotalDefence();

        if ($totalDefenceForEquipped === 0) {
            return 0;
        }

        return $totalDefenceForCompare - $totalDefenceForEquipped;
    }

    public function getAcDecrease(Item $toCompare, Item $equipped): int {
        $totalDefenceForEquipped = $equipped->getTotalDefence();
        $totalDefenceForCompare  = $toCompare->getTotalDefence();

        if (is_null($totalDefenceForEquipped)) {
            return 0;
        }

        if ($totalDefenceForCompare < $totalDefenceForEquipped) {
            return $totalDefenceForCompare - $totalDefenceForEquipped;
        }

        return 0;
    }

    public function getHealIncrease(Item $toCompare, Item $equipped): int {
        $totalHealForEquipped = $equipped->getTotalHealing();
        $totalHealForCompare  = $toCompare->getTotalHealing();

        return $totalHealForCompare - $totalHealForEquipped;
    }

    public function getHealDecrease(Item $toCompare, Item $equipped): int {
        $totalHealForEquipped = $equipped->getTotalHealing();
        $totalHealForCompare  = $toCompare->getTotalHealing();

        if ($totalHealForCompare < $totalHealForEquipped) {
            return $totalHealForCompare - $totalHealForEquipped;
        }

        return 0;
    }

    protected function isItemBetter(Item $toCompare, Item $equipped): bool {
        $totalDamageForEquipped = $equipped->getTotalDamage();
        $totalDamageForCompare  = $toCompare->getTotalDamage();

        $totalDefenceForEquipped = $equipped->getTotalDefence();
        $totalDefenceCompare     = $toCompare->getTotalDefence();

        $totalHealingEquipped = $equipped->getTotalHealing();
        $totalHealingCompare  = $toCompare->getTotalHealing();

        if ($totalDamageForCompare > $totalDamageForEquipped) {
            return true;
        }

        if ($totalDefenceCompare > $totalDefenceForEquipped) {
            return true;
        }

        if ($totalHealingCompare > $totalHealingEquipped) {
            return true;
        }

        return false;
    }
}