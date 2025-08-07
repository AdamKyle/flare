<?php

namespace App\Flare\Items\Enricher;

use App\Flare\Models\Item;

class EquippableEnricher
{
    /**
     * Enrich the given equippable item with computed attributes.
     *
     * This includes:
     * - Total damage, healing, and defense
     * - Stat modifiers
     * - Devouring effects
     * - Affix-based skill summary
     * - Affix-based categorized damage (stackable, non-stacking, irresistible)
     *
     * @param Item $item The item to enrich
     * @param string | null $damageStat
     * @return Item The enriched item instance (mutated in place)
     */
    public function enrich(Item $item, ?string $damageStat = null): Item
    {
        $item->total_damage   = $this->calculateTotalDamage($item);
        $item->total_defence  = $this->calculateTotalDefence($item);
        $item->total_healing  = $this->calculateTotalHealing($item);

        $item->devouring_darkness = $this->calculateDevouringDarkness($item);
        $item->devouring_light    = $this->calculateDevouringLight($item);

        $this->applyStatModifiers($item);

        $item->skill_summary = $this->buildSkillSummary($item);

        $item->total_stackable_affix_damage     = $this->calculateTotalStackableDamage($item);
        $item->total_non_stacking_affix_damage  = $this->calculateTotalNonStackingDamage($item);
        $item->total_irresistible_affix_damage  = $this->calculateTotalIrresistibleDamage($item);

        $item->base_damage_mod = $this->calculateBaseMod($item, 'base_damage_mod');
        $item->base_healing_mod = $this->calculateBaseMod($item, 'base_healing_mod');
        $item->base_ac_mod = $this->calculateBaseMod($item, 'base_ac_mod');

        if (!is_null($damageStat)) {
            $item->total_base_damage_stat = $item->{$damageStat . '_mod'} + $item->getAffixAttribute($damageStat . '_mod');
        }

        return $item;
    }

    /**
     * Calculate total item damage including base and modifiers.
     *
     * @param Item $item
     * @return int
     */
    private function calculateTotalDamage(Item $item): int
    {
        $base = $item->base_damage ?? 0;
        $totalMod = $item->getAffixAttribute('base_damage_mod') + ($item->base_damage_mod ?? 0.0);

        return round($base * (1 + $totalMod));
    }

    /**
     * Calculate total item defense (AC) including modifiers.
     *
     * @param Item $item
     * @return int
     */
    private function calculateTotalDefence(Item $item): int
    {
        $base = $item->base_ac ?? 0;
        $totalMod = $item->getAffixAttribute('base_ac_mod') + ($item->base_ac_mod ?? 0.0);

        return ceil($base * (1 + $totalMod));
    }

    /**
     * Calculate total healing output of the item.
     *
     * @param Item $item
     * @return int
     */
    private function calculateTotalHealing(Item $item): int
    {
        $base = $item->base_healing ?? 0;
        $totalMod = $item->getAffixAttribute('base_healing_mod') + ($item->base_healing_mod ?? 0.0);

        return ceil($base * (1 + $totalMod));
    }

    /**
     * Apply all core stat modifiers (str, dex, int, etc.) directly to the item.
     *
     * @param Item $item
     * @return void
     */
    private function applyStatModifiers(Item $item): void
    {
        foreach (['str', 'dur', 'dex', 'chr', 'int', 'agi', 'focus'] as $stat) {
            $item->{$stat . '_mod'} = $this->calculateSingleStatMod($item, $stat);
        }
    }

    /**
     * Calculate a single stat modifier from base, affix, and holy stack values.
     *
     * @param Item $item
     * @param string $stat
     * @return float
     */
    private function calculateSingleStatMod(Item $item, string $stat): float
    {
        $base  = $item->{$stat . '_mod'} ?? 0.0;
        $affix = $item->getAffixAttribute($stat . '_mod');
        $holy  = $item->holy_stack_stat_bonus ?? 0.0;

        return $base + $affix + $holy;
    }

    /**
     * Calculate devouring darkness (item + holy stacks).
     *
     * @param Item $item
     * @return float
     */
    private function calculateDevouringDarkness(Item $item): float
    {
        $base = $item->devouring_darkness ?? 0.0;
        $holy = $item->holy_stack_devouring_darkness ?? 0.0;

        return $base + $holy;
    }

    /**
     * Calculate devouring light (affix + item).
     *
     * @param Item $item
     * @return float
     */
    private function calculateDevouringLight(Item $item): float
    {
        return ($item->devouring_light ?? 0.0) + $item->getAffixAttribute('devouring_light');
    }

    /**
     * Calculates the total base mod for damage, healing, or AC.
     *
     * @param Item $item
     * @param string $attribute
     * @return float
     */
    private function calculateBaseMod(Item $item, string $attribute): float
    {
        $base  = $item->{$attribute} ?? 0.0;
        $affix = $item->getAffixAttribute($attribute);

        return $base + $affix;
    }


    /**
     * Calculate total affix damage from stackable affixes only.
     *
     * @param Item $item
     * @return float
     */
    private function calculateTotalStackableDamage(Item $item): float
    {

        $total = 0.0;

        if ($item->itemPrefix && $item->itemPrefix->damage_can_stack) {
            $total += $item->itemPrefix->damage_amount;
        }

        if ($item->itemSuffix && $item->itemSuffix->damage_can_stack) {
            $total += $item->itemSuffix->damage_amount;
        }

        return $total;
    }

    /**
     * Calculate total affix damage from non-stacking affixes.
     *
     * @param Item $item
     * @return float
     */
    private function calculateTotalNonStackingDamage(Item $item): float
    {
        $total = 0.0;

        if ($item->itemPrefix && ! $item->itemPrefix->damage_can_stack && $item->itemPrefix->damage_amount > 0) {
            $total += $item->itemPrefix->damage_amount;
        }

        if ($item->itemSuffix && ! $item->itemSuffix->damage_can_stack && $item->itemSuffix->damage_amount > 0) {
            $total += $item->itemSuffix->damage_amount;
        }

        return $total;
    }

    /**
     * Calculate total affix-based irresistible damage.
     *
     * @param Item $item
     * @return float
     */
    private function calculateTotalIrresistibleDamage(Item $item): float
    {
        $total = 0.0;

        if ($item->itemPrefix && $item->itemPrefix->irresistible_damage) {
            $total += $item->itemPrefix->damage_amount;
        }

        if ($item->itemSuffix && $item->itemSuffix->irresistible_damage) {
            $total += $item->itemSuffix->damage_amount;
        }

        return $total;
    }


    /**
     * Build a summary of skill bonuses from item and affixes.
     *
     * @param Item $item
     * @return array<int, array{skill_name: string, skill_training_bonus: float, skill_bonus: float}>
     */
    private function buildSkillSummary(Item $item): array
    {
        $skills = [];

        if ($item->itemPrefix && $item->itemPrefix->skill_name) {
            $skills[] = [
                'skill_name'           => $item->itemPrefix->skill_name,
                'skill_training_bonus' => $item->itemPrefix->skill_training_bonus,
                'skill_bonus'          => $item->itemPrefix->skill_bonus,
            ];
        }

        if ($item->itemSuffix && $item->itemSuffix->skill_name) {
            $skills[] = [
                'skill_name'           => $item->itemSuffix->skill_name,
                'skill_training_bonus' => $item->itemSuffix->skill_training_bonus,
                'skill_bonus'          => $item->itemSuffix->skill_bonus,
            ];
        }

        if ($item->skill_name) {
            $skills[] = [
                'skill_name'           => $item->skill_name,
                'skill_training_bonus' => $item->skill_training_bonus,
                'skill_bonus'          => $item->skill_bonus,
            ];
        }

        return $skills;
    }
}
