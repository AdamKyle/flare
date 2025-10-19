<?php

namespace App\Game\Monsters\Transformers;

use App\Flare\Models\Monster;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;
use League\Fractal\TransformerAbstract;

class MonsterTransformer extends TransformerAbstract
{
    use SkillsTransformerTrait;

    private bool $isSpecial = false;

    private float $locationFlat = 0.0;

    private float $locationPercent = 0.0;

    private float $extraDropChance = 0.0;

    /**
     * Sets whether the monster is special.
     */
    public function setIsMonsterSpecial(bool $isSpecial): MonsterTransformer
    {
        $this->isSpecial = $isSpecial;

        return $this;
    }

    /**
     * Sets a flat increase used for location-based adjustments.
     */
    public function withLocationFlat(float $flat): MonsterTransformer
    {
        $this->locationFlat = $flat;

        return $this;
    }

    /**
     * Sets a percentage (fraction) used for location-based adjustments.
     */
    public function withLocationPercent(float $percent): MonsterTransformer
    {
        $this->locationPercent = $percent;

        return $this;
    }

    /**
     * Sets an extra drop chance (fraction) to add to the base drop chance, capped when computing final.
     */
    public function withExtraDropChance(float $drop): MonsterTransformer
    {
        $this->extraDropChance = $drop;

        return $this;
    }

    /**
     * Fetches the monster response data.
     */
    public function transform(Monster $monster): array
    {
        $hasLocationAdjustments = $this->locationFlat !== 0.0 || $this->locationPercent !== 0.0;

        if (! $hasLocationAdjustments) {
            $shouldIncrease = $this->shouldIncreaseStats($monster);
            $mapPercent = $shouldIncrease ? ($monster->gameMap->enemy_stat_bonus ?? 0.0) : 0.0;

            return [
                'id' => $monster->id,
                'name' => $monster->name,
                'map_name' => $monster->gameMap->name,
                'damage_stat' => $monster->damage_stat,
                'str' => $this->applyPercentToIntegerStat($monster->str, $mapPercent),
                'dur' => $this->applyPercentToIntegerStat($monster->dur, $mapPercent),
                'dex' => $this->applyPercentToIntegerStat($monster->dex, $mapPercent),
                'chr' => $this->applyPercentToIntegerStat($monster->chr, $mapPercent),
                'int' => $this->applyPercentToIntegerStat($monster->int, $mapPercent),
                'agi' => $this->applyPercentToIntegerStat($monster->agi, $mapPercent),
                'focus' => $this->applyPercentToIntegerStat($monster->focus, $mapPercent),
                'to_hit_base' => $this->applyPercentToIntegerStat($monster->dex, $mapPercent),
                'ac' => $this->applyPercentToIntegerStat($monster->ac, $mapPercent),
                'health_range' => $this->applyPercentToRange($monster->health_range, $mapPercent),
                'attack_range' => $this->applyPercentToRange($monster->attack_range, $mapPercent),

                'accuracy' => $this->capAtOne($this->increaseValue($monster->accuracy, $mapPercent)),
                'dodge' => $this->capAtOne($this->increaseValue($monster->dodge, $mapPercent)),
                'casting_accuracy' => $this->capAtOne($this->increaseValue($monster->casting_accuracy, $mapPercent)),

                'criticality' => $this->increaseValue($monster->criticality, $mapPercent),

                'base_stat' => $this->applyPercentToIntegerStat($monster->{$monster->damage_stat}, $mapPercent),
                'max_level' => $monster->max_level,
                'has_damage_spells' => $monster->can_cast,
                'spell_damage' => $this->increaseValue($monster->max_spell_damage, $mapPercent),

                'spell_evasion' => $this->capTo($this->increaseValue($monster->spell_evasion, $mapPercent), 0.95),
                'affix_resistance' => $this->capTo($this->increaseValue($monster->affix_resistance, $mapPercent), 0.95),
                'max_affix_damage' => $this->increaseValue($monster->max_affix_damage, $mapPercent),
                'max_healing' => $this->increaseValue($monster->healing_percentage, $mapPercent),

                'entrancing_chance' => $this->capTo($this->increaseValue($monster->entrancing_chance, $mapPercent), 0.95),
                'devouring_light_chance' => $this->capTo($this->increaseValue($monster->devouring_light_chance, $mapPercent), 0.75),
                'devouring_darkness_chance' => $this->capTo($this->increaseValue($monster->devouring_darkness_chance, $mapPercent), 0.75),

                'ambush_chance' => $monster->ambush_chance,
                'ambush_resistance_chance' => $monster->ambush_resistance,
                'counter_chance' => $monster->counter_chance,
                'counter_resistance_chance' => $monster->counter_resistance,
                'increases_damage_by' => $monster->gameMap->enemy_stat_bonus,
                'is_special' => $this->isSpecial,
                'is_raid_monster' => $monster->is_raid_monster,
                'is_raid_boss' => $monster->is_raid_boss,
                'fire_atonement' => $monster->fire_atonement,
                'ice_atonement' => $monster->ice_atonement,
                'water_atonement' => $monster->water_atonement,
                'life_stealing_resistance' => $monster->life_stealing_resistance,
                'raid_special_attack_type' => $monster->raid_special_attack_type,
                'only_for_location_type' => $monster->only_for_location_type,

                'drop_chance' => $this->computeFinalDropChance($monster),
            ];
        }

        $shouldIncrease = $this->shouldIncreaseStats($monster);
        $mapPercent = $shouldIncrease ? ($monster->gameMap->enemy_stat_bonus ?? 0.0) : 0.0;
        $effectivePercent = $mapPercent + $this->locationPercent;

        return [
            'id' => $monster->id,
            'name' => $monster->name,
            'map_name' => $monster->gameMap->name,
            'damage_stat' => $monster->damage_stat,

            'str' => $this->applyFlatAndPercentToIntegerStat($monster->str, $this->locationFlat, $effectivePercent),
            'dur' => $this->applyFlatAndPercentToIntegerStat($monster->dur, $this->locationFlat, $effectivePercent),
            'dex' => $this->applyFlatAndPercentToIntegerStat($monster->dex, $this->locationFlat, $effectivePercent),
            'chr' => $this->applyFlatAndPercentToIntegerStat($monster->chr, $this->locationFlat, $effectivePercent),
            'int' => $this->applyFlatAndPercentToIntegerStat($monster->int, $this->locationFlat, $effectivePercent),
            'agi' => $this->applyFlatAndPercentToIntegerStat($monster->agi, $this->locationFlat, $effectivePercent),
            'focus' => $this->applyFlatAndPercentToIntegerStat($monster->focus, $this->locationFlat, $effectivePercent),
            'to_hit_base' => $this->applyFlatAndPercentToIntegerStat($monster->dex, $this->locationFlat, $effectivePercent),
            'ac' => $this->applyFlatAndPercentToIntegerStat($monster->ac, $this->locationFlat, $effectivePercent),

            'health_range' => $this->applyFlatAndPercentToRange($monster->health_range, $this->locationFlat, $effectivePercent),
            'attack_range' => $this->applyFlatAndPercentToRange($monster->attack_range, $this->locationFlat, $effectivePercent),

            'accuracy' => $this->capAtOne($this->increaseValue($monster->accuracy, $effectivePercent)),
            'dodge' => $this->capAtOne($this->increaseValue($monster->dodge, $effectivePercent)),
            'casting_accuracy' => $this->capAtOne($this->increaseValue($monster->casting_accuracy, $effectivePercent)),

            'criticality' => $this->increaseValue($monster->criticality, $effectivePercent),

            'base_stat' => $this->applyFlatAndPercentToIntegerStat($monster->{$monster->damage_stat}, $this->locationFlat, $effectivePercent),
            'max_level' => $monster->max_level,
            'has_damage_spells' => $monster->can_cast,
            'spell_damage' => $this->increaseValue($monster->max_spell_damage, $effectivePercent),

            'spell_evasion' => $this->capTo($this->increaseValue($monster->spell_evasion, $effectivePercent), 0.95),
            'affix_resistance' => $this->capTo($this->increaseValue($monster->affix_resistance, $effectivePercent), 0.95),
            'max_affix_damage' => $this->increaseValue($monster->max_affix_damage, $effectivePercent),
            'max_healing' => $this->increaseValue($monster->healing_percentage, $effectivePercent),

            'entrancing_chance' => $this->capTo($this->increaseValue($monster->entrancing_chance, $effectivePercent), 0.95),
            'devouring_light_chance' => $this->capTo($this->increaseValue($monster->devouring_light_chance, $effectivePercent), 0.75),
            'devouring_darkness_chance' => $this->capTo($this->increaseValue($monster->devouring_darkness_chance, $effectivePercent), 0.75),

            'ambush_chance' => $monster->ambush_chance,
            'ambush_resistance_chance' => $monster->ambush_resistance,
            'counter_chance' => $monster->counter_chance,
            'counter_resistance_chance' => $monster->counter_resistance,
            'increases_damage_by' => $monster->gameMap->enemy_stat_bonus,
            'is_special' => $this->isSpecial,
            'is_raid_monster' => $monster->is_raid_monster,
            'is_raid_boss' => $monster->is_raid_boss,
            'fire_atonement' => $monster->fire_atonement,
            'ice_atonement' => $monster->ice_atonement,
            'water_atonement' => $monster->water_atonement,
            'life_stealing_resistance' => $monster->life_stealing_resistance,
            'raid_special_attack_type' => $monster->raid_special_attack_type,
            'only_for_location_type' => $monster->only_for_location_type,

            'drop_chance' => $this->computeFinalDropChance($monster),
        ];
    }

    /**
     * Applies percentage to an integer-like stat (no flat).
     */
    private function applyPercentToIntegerStat(int|float|null $value, float $percent): int
    {
        $base = (int) round($value ?? 0);

        return (int) round($base + $base * $percent);
    }

    /**
     * Applies flat and percentage to an integer-like stat.
     */
    private function applyFlatAndPercentToIntegerStat(int|float|null $value, float $flat, float $percent): int
    {
        $base = (int) round(($value ?? 0) + $flat);

        return (int) round($base + $base * $percent);
    }

    /**
     * Applies percentage to a "min-max" range string (no flat).
     */
    private function applyPercentToRange(string $range, float $percent): string
    {
        $parts = explode('-', $range);

        $min = isset($parts[0]) ? (int) $parts[0] : 0;
        $max = isset($parts[1]) ? (int) $parts[1] : 0;

        $min = (int) round($min + $min * $percent);
        $max = (int) round($max + $max * $percent);

        return $min.'-'.$max;
    }

    /**
     * Applies flat and percentage to a "min-max" range string.
     */
    private function applyFlatAndPercentToRange(string $range, float $flat, float $percent): string
    {
        $parts = explode('-', $range);

        $min = isset($parts[0]) ? (int) $parts[0] : 0;
        $max = isset($parts[1]) ? (int) $parts[1] : 0;

        $min = (int) round($min + $flat);
        $max = (int) round($max + $flat);

        $min = (int) round($min + $min * $percent);
        $max = (int) round($max + $max * $percent);

        return $min.'-'.$max;
    }

    /**
     * Computes the final drop chance, using a capped base drop_check and capping final at 1.0.
     */
    private function computeFinalDropChance(Monster $monster): float
    {
        $baseDrop = $monster->drop_check ?? 0.0;

        if ($baseDrop > 0.99) {
            $baseDrop = 0.99;
        }

        $final = $baseDrop + $this->extraDropChance;

        if ($final > 1.0) {
            $final = 1.0;
        }

        return $final;
    }

    /**
     * Clamps a probability-like value to a maximum.
     */
    private function capTo(float|int $value, float $max): float
    {
        return $value > $max ? $max : (float) $value;
    }

    /**
     * Clamps a probability-like value to 1.0 maximum.
     */
    private function capAtOne(float|int $value): float
    {
        return $this->capTo($value, 1.0);
    }

    /**
     * Increase stat.
     */
    public function increaseValue(int|float|null $statValue = null, ?float $increaseBy = null): int|float
    {
        if (is_null($increaseBy)) {
            return $statValue;
        }

        if ($statValue === 0 || $statValue === 0.0 || is_null($statValue)) {
            return $increaseBy;
        }

        $increaseBy = $statValue + $statValue * $increaseBy;

        if (is_float($statValue)) {
            if ($increaseBy >= 1) {
                $increaseBy = 1.0;
            }
        }

        return $increaseBy;
    }

    /**
     * Determines if stats should increase based on the game map.
     */
    public function shouldIncreaseStats(Monster $monster): bool
    {
        return match ($monster->gameMap->name) {
            'Shadow Plane', 'Hell', 'Purgatory', 'The Ice Plane', 'Twisted Memories', 'Delusional Memories' => true,
            default => false,
        };
    }
}
