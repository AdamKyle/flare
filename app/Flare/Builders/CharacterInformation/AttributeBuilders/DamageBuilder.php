<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;


class DamageBuilder extends BaseAttribute {


    public function buildWeaponDamage(float $damageStat, bool $voided = false): float {

        $baseDamage = $damageStat * .05;
        $baseDamage = $baseDamage < 1 ? 1 : $baseDamage;

        $itemDamage      = $this->getDamageFromItems('weapon');
        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage('weapon')) {
            $skillPercentage = $this->fetchBaseAttributeFromSkills('base_damage');
        }

        $totalDamage = $baseDamage + $itemDamage;

        if ($voided) {
            return $totalDamage + $totalDamage * $skillPercentage;
        }

        $affixPercentage = $this->getAttributeBonusFromAllItemAffixes('base_damage');

        return $totalDamage + $totalDamage * ($skillPercentage + $affixPercentage);
    }

    public function buildRingDamage(): int {
        return 0;
    }

    public function buildSpellDamage(): int {
        return 0;
    }

    public function buildAffixStackingDamage(): int {
        return 0;
    }

    public function buildAffixNonStackingDamage(): int {
        return 0;
    }

    public function buildLifeStealingDamage(): float {
        return 0;
    }

    protected function getDamageFromItems(string $type): int {
        return $this->inventory->where('item.type', $type)->sum('item.base_damage');
    }

    protected function shouldIncludeSkillDamage(string $type): bool {
        $class = $this->character->class;

        switch($type) {
            case 'weapon':
                return $class->type()->isNonCaster();
            default:
                false;
        }
    }
}
