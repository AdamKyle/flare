<?php

namespace App\Game\Core\Comparison;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Game\Core\Exceptions\EquipItemException;
use Illuminate\Database\Eloquent\Collection;

class WeaponComparison implements ComparisonContract {

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

        $foundHand = $inventorySlots->filter(function($slot) use ($hand) {
            return $slot->position === $hand;
        })->first();

        if ($this->isItemBetter($toCompare, $foundHand->item)) {
            return [
                'is_better'         => true,
                'replaces_item'     => $foundHand->item,
                'slot'              => $foundHand,
                'position'          => $foundHand->position,
                'damage_adjustment' => $this->getDamageIncrease($toCompare, $foundHand->item),
            ];
        } else {
            return [
                'is_better'         => false,
                'replaces_item'     => null,
                'slot'              => $foundHand,
                'position'          => $foundHand->position,
                'damage_adjustment' => $this->getDamageDecrease($toCompare, $foundHand->item),
            ];
        }
    }

    protected function isItemBetter(Item $toCompare, Item $equipped): bool {
        $totalDamageForEquipped = $this->getItemDamage($equipped);
        $totalDamageForCompare  = $this->getItemDamage($toCompare);

        if ($totalDamageForCompare > $totalDamageForEquipped) {
            return true;
        }

        return false;
    }

    public function getDamageIncrease(Item $toCompare, Item $equipped): int {
        $totalDamageForEquipped = $this->getItemDamage($equipped);
        $totalDamageForCompare  = $this->getItemDamage($toCompare);

        return $totalDamageForCompare - $totalDamageForEquipped;
    }

    public function getDamageDecrease(Item $toCompare, Item $equipped) {
        $totalDamageForEquipped = $this->getItemDamage($equipped);
        $totalDamageForCompare  = $this->getItemDamage($toCompare);

        if ($totalDamageForCompare < $totalDamageForEquipped) {
            return $totalDamageForCompare - $totalDamageForEquipped;
        }

        return 0;
    }

    protected function getItemDamage(Item $item): int {
        $attack = $item->base_damage;


        $artifact = $item->artifactProperty;
        
        if (!is_null($artifact)) {
            $attack += $artifact->base_damage_mod;
        }

        $affixes = $item->itemAffixes;

        if ($affixes->isNotEmpty()) {
            foreach($affixes as $affix) {
                $attack += $affix->base_damage_mod;
            }
        }

        return $attack;
    }
}