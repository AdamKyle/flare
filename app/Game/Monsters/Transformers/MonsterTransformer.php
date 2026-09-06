<?php

namespace App\Game\Monsters\Transformers;

use App\Flare\Models\Monster;
use App\Game\Gems\Values\AreaGemMonsterEffect;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Gems\Values\GemTypeValue;
use App\Game\Gems\Values\ResolvedAreaGemAtonement;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Gems\Values\ResolvedAreaGemSource;
use League\Fractal\TransformerAbstract;

/**
 * Transforms a persisted Monster into its effective, cache-ready payload
 * using the resolved area (Map/Location) Gem effects for the Monster's
 * current gameplay context.
 */
class MonsterTransformer extends TransformerAbstract
{
    private ?ResolvedAreaGemEffects $areaEffects = null;

    /**
     * Return a clone of this transformer configured with the resolved area Gem effects to apply.
     */
    public function withAreaGemEffects(ResolvedAreaGemEffects $areaEffects): MonsterTransformer
    {
        $clone = clone $this;
        $clone->areaEffects = $areaEffects;

        return $clone;
    }

    /**
     * Build the effective transformed Monster payload for the configured area Gem effects.
     *
     * @return array<string, mixed>
     */
    public function transform(Monster $monster): array
    {
        $areaEffects = $this->resolvedAreaEffects();

        $enemyStrengthIncrease = $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE);
        $healingIncrease = $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_HEALING_INCREASE);

        $questItemDropChance = $this->addRatio(
            $monster->quest_item_drop_chance,
            $areaEffects->rewardEffect(AreaGemRewardEffect::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE),
            1.0
        );

        $xp = $this->increaseRewardAmount($monster->xp, $areaEffects->rewardEffect(AreaGemRewardEffect::MONSTER_XP_INCREASE));
        $gold = $this->increaseRewardAmount($monster->gold, $areaEffects->rewardEffect(AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE));

        [$fireAtonement, $iceAtonement, $waterAtonement] = $this->resolveAtonement($monster, $areaEffects->monsterAtonement());

        return [
            'id' => $monster->id,
            'name' => $monster->name,
            'map_name' => $monster->gameMap->name,
            'damage_stat' => $monster->damage_stat,
            'to_hit_base' => $this->applyPercentToIntegerStat($monster->{$monster->damage_stat}, $enemyStrengthIncrease),

            'str' => $this->applyPercentToIntegerStat($monster->str, $enemyStrengthIncrease),
            'dur' => $this->applyPercentToIntegerStat($monster->dur, $enemyStrengthIncrease),
            'dex' => $this->applyPercentToIntegerStat($monster->dex, $enemyStrengthIncrease),
            'chr' => $this->applyPercentToIntegerStat($monster->chr, $enemyStrengthIncrease),
            'int' => $this->applyPercentToIntegerStat($monster->int, $enemyStrengthIncrease),
            'agi' => $this->applyPercentToIntegerStat($monster->agi, $enemyStrengthIncrease),
            'focus' => $this->applyPercentToIntegerStat($monster->focus, $enemyStrengthIncrease),
            'ac' => $this->applyPercentToIntegerStat($monster->ac, $enemyStrengthIncrease),

            'health_range' => $this->applyPercentToRange($monster->health_range, $enemyStrengthIncrease),
            'attack_range' => $this->applyPercentToRange($monster->attack_range, $enemyStrengthIncrease),

            'accuracy' => $this->increaseRatio($monster->accuracy, $enemyStrengthIncrease, 1.0),
            'dodge' => $this->increaseRatio($monster->dodge, $enemyStrengthIncrease, 1.0),
            'casting_accuracy' => $this->increaseRatio($monster->casting_accuracy, $enemyStrengthIncrease, 1.0),

            'criticality' => $this->increaseNumeric($monster->criticality, $enemyStrengthIncrease),

            'max_level' => $monster->max_level,
            'has_damage_spells' => $monster->can_cast,
            'spell_damage' => $this->increaseNumeric($monster->max_spell_damage, $enemyStrengthIncrease),

            'spell_evasion' => $this->addRatio($monster->spell_evasion, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_SPELL_EVASION), 0.95),
            'affix_resistance' => $this->addRatio($monster->affix_resistance, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_AFFIX_RESISTANCE), 0.95),
            'max_affix_damage' => $this->increaseNumeric($monster->max_affix_damage, $enemyStrengthIncrease),
            'max_healing' => $this->increaseNumeric($monster->healing_percentage, $healingIncrease),

            'entrancing_chance' => $this->addRatio($monster->entrancing_chance, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_ENTRANCING_CHANCE), 0.95),
            'devouring_light_chance' => $this->addRatio($monster->devouring_light_chance, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_DEVOURING_LIGHT_CHANCE), 0.75),
            'devouring_darkness_chance' => $this->addRatio($monster->devouring_darkness_chance, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_DEVOURING_DARKNESS_CHANCE), 0.75),

            'ambush_chance' => $this->addRatio($monster->ambush_chance, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_AMBUSH_CHANCE), 1.0),
            'ambush_resistance_chance' => $this->addRatio($monster->ambush_resistance, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_AMBUSH_RESISTANCE), 1.0),
            'counter_chance' => $this->addRatio($monster->counter_chance, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_COUNTER_CHANCE), 1.0),
            'counter_resistance_chance' => $this->addRatio($monster->counter_resistance, $areaEffects->monsterEffect(AreaGemMonsterEffect::ENEMY_COUNTER_RESISTANCE), 1.0),

            'increases_damage_by' => $enemyStrengthIncrease,
            'is_raid_monster' => $monster->is_raid_monster,
            'is_raid_boss' => $monster->is_raid_boss,
            'fire_atonement' => $fireAtonement,
            'ice_atonement' => $iceAtonement,
            'water_atonement' => $waterAtonement,
            'life_stealing_resistance' => $monster->life_stealing_resistance,
            'raid_special_attack_type' => $monster->raid_special_attack_type,
            'only_for_location_type' => $monster->only_for_location_type,

            'drop_chance' => min($monster->drop_check ?? 0.0, 1.0),
            'quest_item_drop_chance' => $questItemDropChance,

            'xp' => $xp,
            'gold' => $gold,
            'gold_cost' => $monster->gold_cost,
            'gold_dust_cost' => $monster->gold_dust_cost,
            'shard_reward' => $monster->shards,
            'is_celestial_entity' => $monster->is_celestial_entity,

            'gem_effect_context' => $this->buildGemEffectContext(),
        ];
    }

    /**
     * Resolve the configured area Gem effects, defaulting to a no-effect result when none were configured.
     */
    private function resolvedAreaEffects(): ResolvedAreaGemEffects
    {
        return $this->areaEffects ?? ResolvedAreaGemEffects::none();
    }

    /**
     * Increase a whole-number reward amount (XP/Gold) by a percent and round to the nearest integer.
     */
    private function increaseRewardAmount(int|float|null $value, float $percent): int
    {
        $base = $value ?? 0;

        return round($base * (1 + $percent));
    }

    /**
     * Apply a percent multiplier to an integer-like stat and round to int.
     */
    private function applyPercentToIntegerStat(int|float|null $value, float $percent): int
    {
        $base = round($value ?? 0);

        return round($base * (1 + $percent));
    }

    /**
     * Apply a percent multiplier to a "min-max" range string.
     */
    private function applyPercentToRange(string $range, float $percent): string
    {
        [$minStr, $maxStr] = array_pad(explode('-', $range, 2), 2, '0');

        $min = round($minStr * (1 + $percent));
        $max = round($maxStr * (1 + $percent));

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
     * Increase a ratio/probability by a percent multiplier and clamp to a maximum cap.
     */
    private function increaseRatio(float|int|null $value, float $percent, float $cap): float
    {
        $base = $value ?? 0.0;

        return min($base * (1 + $percent), $cap);
    }

    /**
     * Add a resolved Gem ratio effect to a base ratio/probability and clamp to a maximum cap.
     */
    private function addRatio(float|int|null $value, float $effect, float $cap): float
    {
        $base = $value ?? 0.0;

        return min($base + $effect, $cap);
    }

    /**
     * Resolve the effective elemental atonement, applying the precedence rule that a
     * Monster's own persisted positive atonement always wins over any Gem atonement.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function resolveAtonement(Monster $monster, ResolvedAreaGemAtonement $atonement): array
    {
        $fire = $monster->fire_atonement ?? 0.0;
        $ice = $monster->ice_atonement ?? 0.0;
        $water = $monster->water_atonement ?? 0.0;

        if ($fire > 0.0 || $ice > 0.0 || $water > 0.0) {
            return [$fire, $ice, $water];
        }

        if (! $atonement->hasEffect()) {
            return [$fire, $ice, $water];
        }

        return match ($atonement->type()) {
            GemTypeValue::FIRE => [$atonement->amount(), $ice, $water],
            GemTypeValue::ICE => [$fire, $atonement->amount(), $water],
            GemTypeValue::WATER => [$fire, $ice, $atonement->amount()],
            default => [$fire, $ice, $water],
        };
    }

    /**
     * Build the Gem effect context metadata describing why/how this Monster was transformed.
     *
     * @return array<string, mixed>
     */
    private function buildGemEffectContext(): array
    {
        $areaEffects = $this->resolvedAreaEffects();

        if (! $areaEffects->hasAnyEffects()) {
            return ['has_effects' => false];
        }

        return [
            'has_effects' => true,
            'context_type' => $areaEffects->contextType()?->value,
            'context_label' => $areaEffects->contextLabel(),
            'source_game_map_id' => $areaEffects->sourceGameMapId(),
            'game_map' => [
                'id' => $areaEffects->currentGameMapId(),
                'name' => $areaEffects->currentGameMapName(),
            ],
            'location' => is_null($areaEffects->locationId()) ? null : [
                'id' => $areaEffects->locationId(),
                'name' => $areaEffects->locationName(),
            ],
            'sources' => array_map(static fn (ResolvedAreaGemSource $source): array => $source->toArray(), $areaEffects->sources()),
            'character_power_reduction' => $areaEffects->characterPowerReduction(),
            'monster_effects' => $areaEffects->monsterEffects()->toArray(),
            'reward_effects' => $areaEffects->rewardEffects()->toArray(),
        ];
    }
}
