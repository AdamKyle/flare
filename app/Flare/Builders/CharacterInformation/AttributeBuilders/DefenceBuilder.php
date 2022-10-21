<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;

use Illuminate\Support\Collection;

class DefenceBuilder extends BaseAttribute {

    public function buildDefence(bool $voided = false): int {
        $baseAc      = $this->character->ac;
        $skillBonus  = $this->fetchBaseAttributeFromSkills('base_ac');

        if (is_null($this->inventory)) {
            return $baseAc + $baseAc * $skillBonus;
        }

        $armourSlots = $this->getItemsWithBaseAC();
        $itemAC      = $this->getACFromItems($armourSlots);

        if ($voided) {
            return $itemAC + $itemAC * $skillBonus;
        }

        $affixBonus = $this->getAttributeBonusFromAllItemAffixes('base_ac');

        $itemAC = $itemAC + $itemAC * ($skillBonus + $affixBonus);

        return intval($baseAc + $itemAC);
    }

    protected function getACFromItems(Collection $slots): int {
        $ac = 0;

        if ($slots->isEmpty()) {
            return $ac;
        }

        $ac = $slots->sum('item.base_ac');

        return intval($ac / $slots->count());
    }

    protected function getItemsWithBaseAC(): Collection {
        return $this->inventory->filter(function ($slot) {
            return $slot->item->base_ac > 0;
        });
    }
}
