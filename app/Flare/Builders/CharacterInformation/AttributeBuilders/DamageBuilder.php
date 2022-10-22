<?php

namespace App\Flare\Builders\CharacterInformation\AttributeBuilders;


class DamageBuilder extends BaseAttribute {

    public function buildWeaponDamage(float $damageStat, bool $voided = false, string $position = 'both'): float {
        $class      = $this->character->class;
        $baseDamage = $damageStat * .05;
        $baseDamage = $baseDamage < 1 ? 1 : $baseDamage;

        $itemDamage      = $this->getDamageFromItems('weapon', $position);
        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class,'weapon')) {
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
        return $this->getDamageFromItems('ring', 'both');
    }

    public function buildSpellDamage(float $damageStat, bool $voided = false, $position = 'both'): float {
        $class = $this->character->class;

        if ($class->type()->isCaster()) {
            $baseDamage = $damageStat * 0.05;
        } else {
            $baseDamage = 0;
        }

        $itemDamage      = $this->getDamageFromItems('spell-damage', $position);

        $skillPercentage = 0;

        if ($this->shouldIncludeSkillDamage($class,'spell')) {
            $skillPercentage = $this->fetchBaseAttributeFromSkills('base_damage');
        }

        $totalDamage = $baseDamage + $itemDamage;

        if ($voided) {
            return $totalDamage + $totalDamage * $skillPercentage;
        }

        $affixPercentage = $this->getAttributeBonusFromAllItemAffixes('base_damage');

        return $totalDamage + $totalDamage * ($skillPercentage + $affixPercentage);
    }

    public function buildAffixStackingDamage(bool $voided = false): int {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $itemSuffix = $this->inventory->where('item.itemSuffix.damage_can_stack', true)->sum('item.itemSuffix.damage');
        $itemPrefix = $this->inventory->where('item.itemPrefix.damage_can_stack', true)->sum('item.itemPrefix.damage');

        return $itemSuffix + $itemPrefix;
    }

    public function buildAffixNonStackingDamage(bool $voided = false): int {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $itemSuffix = $this->inventory->where('item.itemSuffix.damage_can_stack', false)->sum('item.itemSuffix.damage');
        $itemPrefix = $this->inventory->where('item.itemPrefix.damage_can_stack', false)->sum('item.itemPrefix.damage');

        $amounts = array_filter([$itemPrefix, $itemSuffix]);

        if (empty($amounts)) {
            return 0.0;
        }

        return max($amounts);
    }

    public function buildIrresistibleNonStackingAffixDamage(bool $voided = false): int {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $itemSuffix = $this->inventory->where('item.itemSuffix.damage_can_stack', false)
                                      ->where('item.itemSuffix.irresistible_damage', false)
                                      ->sum('item.itemSuffix.damage');
        $itemPrefix = $this->inventory->where('item.itemPrefix.damage_can_stack', false)
                                      ->where('item.itemPrefix.irresistible_damage', false)
                                      ->sum('item.itemPrefix.damage');

        $amounts = array_filter([$itemPrefix, $itemSuffix]);

        if (empty($amounts)) {
            return 0.0;
        }

        return max($amounts);
    }

    public function buildIrresistibleStackingAffixDamage(bool $voided = false): int {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $itemSuffix = $this->inventory->where('item.itemSuffix.damage_can_stack', true)
                                      ->where('item.itemSuffix.irresistible_damage', true)
                                      ->sum('item.itemSuffix.damage');
        $itemPrefix = $this->inventory->where('item.itemPrefix.damage_can_stack', true)
                                      ->where('item.itemPrefix.irresistible_damage', true)
                                      ->sum('item.itemPrefix.damage');

        return $itemPrefix + $itemSuffix;
    }

    public function buildLifeStealingDamage(bool $voided = false): float {

        if ($voided || is_null($this->inventory)) {
            return 0;
        }

        $class = $this->character->class;

        if ($class->type()->isVampire()) {
            $itemSuffix = $this->inventory->sum('item.itemSuffix.steal_life_amount');
            $itemPrefix = $this->inventory->sum('item.itemPrefix.steal_life_amount');

            $lifeStealAmount  = $itemSuffix + $itemPrefix;
            $gameMap          = $this->character->map->gameMap;

            if ($gameMap->mapType()->isHell() || $gameMap->mapType()->isPurgatory()) {
                $lifeStealAmount = $lifeStealAmount / 2;
            }

            if ($lifeStealAmount >= 1) {
                $lifeStealAmount =  0.99;
            }

            return $lifeStealAmount;
        }

        $lifeStealAmounts = [
            $this->inventory->max('item.itemSuffix.steal_life_amount'),
            $this->inventory->max('item.itemPrefix.steal_life_amount'),
        ];

        $lifeStealAmounts = array_filter($lifeStealAmounts);

        if (empty($lifeStealAmounts)) {
            return 0;
        }

        return max($lifeStealAmounts);
    }
}
