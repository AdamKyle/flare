<?php

namespace App\Game\Monsters\Transformers;

use App\Flare\Models\Monster;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;
use League\Fractal\TransformerAbstract;

/**
 * MonsterTransformer
 *
 * Computes transformed monster stats using a single enemy increase percent and a map-only drop chance increase.
 *
 * Caps:
 * - Drop chance: 1.0
 * - Devouring Light: 0.75
 * - Devouring Darkness: 0.75
 * - Entrancing chance: 0.95
 * - Spell evasion: 0.95
 * - Affix resistance: 0.95
 */
class MonsterTransformer extends TransformerAbstract
{
    use SkillsTransformerTrait;

    /*
     * @var bool $isSpecial
     */
    private bool $isSpecial = false;

    /*
     * @var float $enemyIncrease
     */
    private float $enemyIncrease = 0.0;

    /*
     * @var float $dropChanceIncrease
     */
    private float $dropChanceIncrease = 0.0;

    /**
     * Set whether this monster should be flagged as special.
     */
    public function setIsMonsterSpecial(bool $isSpecial): MonsterTransformer
    {
        $this->isSpecial = $isSpecial;

        return $this;
    }

    /**
     * Provide the total enemy stat increase percent (location + map).
     */
    public function withEnemyIncrease(float $percent): MonsterTransformer
    {
        $this->enemyIncrease = $percent;

        return $this;
    }

    /**
     * Provide the drop chance increase percent (map-only).
     */
    public function withDropChanceIncrease(float $percent): MonsterTransformer
    {
        $this->dropChanceIncrease = $percent;

        return $this;
    }

    /**
     * Build the transformed monster payload applying enemy and drop chance increases.
     */
    public function transform(Monster $monster): array
    {
        $enemyIncrease = $this->enemyIncrease;

        return [
            'id' => $monster->id,
            'name' => $monster->name,
            'map_name' => $monster->gameMap->name,
            'damage_stat' => $monster->damage_stat,

            'str' => $this->applyPercentToIntegerStat($monster->str, $enemyIncrease),
            'dur' => $this->applyPercentToIntegerStat($monster->dur, $enemyIncrease),
            'dex' => $this->applyPercentToIntegerStat($monster->dex, $enemyIncrease),
            'chr' => $this->applyPercentToIntegerStat($monster->chr, $enemyIncrease),
            'int' => $this->applyPercentToIntegerStat($monster->int, $enemyIncrease),
            'agi' => $this->applyPercentToIntegerStat($monster->agi, $enemyIncrease),
            'focus' => $this->applyPercentToIntegerStat($monster->focus, $enemyIncrease),
            'ac' => $this->applyPercentToIntegerStat($monster->ac, $enemyIncrease),

            'health_range' => $this->applyPercentToRange($monster->health_range, $enemyIncrease),
            'attack_range' => $this->applyPercentToRange($monster->attack_range, $enemyIncrease),

            'accuracy' => $this->increaseRatio($monster->accuracy, $enemyIncrease, 1.0),
            'dodge' => $this->increaseRatio($monster->dodge, $enemyIncrease, 1.0),
            'casting_accuracy' => $this->increaseRatio($monster->casting_accuracy, $enemyIncrease, 1.0),

            'criticality' => $this->increaseNumeric($monster->criticality, $enemyIncrease),

            'max_level' => $monster->max_level,
            'has_damage_spells' => $monster->can_cast,
            'spell_damage' => $this->increaseNumeric($monster->max_spell_damage, $enemyIncrease),

            'spell_evasion' => $this->increaseRatio($monster->spell_evasion, $enemyIncrease, 0.95),
            'affix_resistance' => $this->increaseRatio($monster->affix_resistance, $enemyIncrease, 0.95),
            'max_affix_damage' => $this->increaseNumeric($monster->max_affix_damage, $enemyIncrease),
            'max_healing' => $this->increaseNumeric($monster->healing_percentage, $enemyIncrease),

            'entrancing_chance' => $this->increaseRatio($monster->entrancing_chance, $enemyIncrease, 0.95),
            'devouring_light_chance' => $this->increaseRatio($monster->devouring_light_chance, $enemyIncrease, 0.75),
            'devouring_darkness_chance' => $this->increaseRatio($monster->devouring_darkness_chance, $enemyIncrease, 0.75),

            'ambush_chance' => $monster->ambush_chance,
            'ambush_resistance_chance' => $monster->ambush_resistance,
            'counter_chance' => $monster->counter_chance,
            'counter_resistance_chance' => $monster->counter_resistance,

            'increases_damage_by' => $this->enemyIncrease,
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

            'xp' => $monster->xp,
            'gold' => $monster->gold,
            'gold_cost' => $monster->gold_cost,
            'gold_dust_cost' => $monster->gold_dust_cost,
            'shard_reward' => $monster->shards,
            'is_celestial_entity' => $monster->is_celestial_entity,
        ];
    }

    /**
     * Apply a percent multiplier to an integer-like stat and round to int.
     */
    private function applyPercentToIntegerStat(int|float|null $value, float $percent): int
    {
        $base = (int) round($value ?? 0);

        return (int) round($base * (1 + $percent));
    }

    /**
     * Apply a percent multiplier to a "min-max" range string.
     */
    private function applyPercentToRange(string $range, float $percent): string
    {
        [$minStr, $maxStr] = array_pad(explode('-', $range, 2), 2, '0');

        $min = (int) $minStr;
        $max = (int) $maxStr;

        $min = (int) round($min * (1 + $percent));
        $max = (int) round($max * (1 + $percent));

        return $min.'-'.$max;
    }

    /**
     * Increase a numeric (non-probability) stat by a percent.
     */
    private function increaseNumeric(float|int|null $value, float $percent): float|int
    {
        $base = $value ?? 0;

        return $base + ($base * $percent);
    }

    /**
     * Increase a ratio/probability by a percent and clamp to a maximum cap.
     */
    private function increaseRatio(float|int|null $value, float $percent, float $cap): float
    {
        $base = $value ?? 0.0;

        $raised = $base * (1 + $percent);

        return min($raised, $cap);
    }

    /**
     * Compute final drop chance using base drop_check plus map-only increase, clamped to 1.0.
     * The base drop_check is capped at 0.99 before applying the increase.
     */
    private function computeFinalDropChance(Monster $monster): float
    {
        $base = $monster->drop_check ?? 0.0;

        $base = min($base, 0.99);

        $final = $base + $this->dropChanceIncrease;

        return min($final, 1.0);
    }
}
