<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;


class HealingBuilder extends BaseAttribute {

    public function buildHealing(float $damageStat, bool $voided = false, string $position = 'both'): float {
        $class = $this->character->class;

        if ($class->type()->isHealer()) {
            $baseDamage = $damageStat * 0.05;
        } else {
            $baseDamage = 0;
        }

        $itemDamage      = $this->getHealingFromItems('spell-healing', $position);

        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class,'healing')) {
            $skillPercentage = $this->fetchBaseAttributeFromSkills('base_healing');
        }

        $totalDamage = $baseDamage + $itemDamage;

        if ($voided) {
            return $totalDamage + $totalDamage * $skillPercentage;
        }

        $affixPercentage = $this->getAttributeBonusFromAllItemAffixes('base_healing');

        return $totalDamage + $totalDamage * ($skillPercentage + $affixPercentage);
    }
}
