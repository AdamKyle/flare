<?php

namespace App\Game\Character\Builders\InformationBuilders\AttributeBuilders;

use Illuminate\Support\Collection;

class DefenceBuilder extends BaseAttribute
{
    /**
     * Build defence.
     */
    public function buildDefence(float $classBonus, bool $voided = false): int
    {
        $baseAc = $this->character->ac;
        $skillBonus = $this->fetchBaseAttributeFromSkills('base_ac');

        if (is_null($this->inventory)) {
            return $baseAc + $baseAc * $skillBonus;
        }

        $armourSlots = $this->getItemsWithBaseAC();
        $itemAC = $this->getACFromItems($armourSlots);

        $hasShield = $armourSlots->filter(function ($slot) {
            return $slot->item->type === 'shield';
        })->isNotEmpty();

        if (! $hasShield) {
            $classBonus = 0.0;
        }

        if ($voided) {
            return $itemAC + $itemAC * ($skillBonus + $classBonus);
        }

        $affixBonus = $this->getAttributeBonusFromAllItemAffixes('base_ac');

        $itemAC = $itemAC + $itemAC * ($skillBonus + $affixBonus + $classBonus);

        return intval($baseAc + $itemAC);
    }

    public function buildDefenceBreakDownDetails(bool $voided = false): array
    {
        $details = [];

        $details['base_ac'] = $this->character->ac;
        $details['ac_from_items'] = number_format($this->getACFromItems($this->getItemsWithBaseAC()));
        $details['skill_effecting_ac'] = $this->fetchBaseAttributeFromSkillsDetails('base_ac');
        $details['attached_affixes'] = $this->getAttributeBonusFromAllItemAffixesDetails('base_ac', $voided);

        return $details;
    }

    /**
     * Get base ac from items and divide by amount of armour equipped.
     */
    protected function getACFromItems(Collection $slots): int
    {
        $ac = 0;

        if ($slots->isEmpty()) {
            return $ac;
        }

        $ac = $slots->sum('item.base_ac');

        return intval($ac / $slots->count());
    }

    /**
     * Get all items with a base AC.
     */
    protected function getItemsWithBaseAC(): Collection
    {

        if (is_null($this->inventory)) {
            return collect();
        }

        return $this->inventory->filter(function ($slot) {
            return $slot->item->base_ac > 0;
        });
    }
}
