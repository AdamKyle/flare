<?php

namespace App\Game\Gems\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Flare\Models\Character;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Gem;
use App\Flare\Models\Location;
use App\Game\Gems\Values\AreaGemContext;
use App\Game\Gems\Values\GemSourceType;
use App\Game\Gems\Values\ResolvedAreaGemAtonement;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Gems\Values\ResolvedAreaGemMonsterEffects;
use App\Game\Gems\Values\ResolvedAreaGemRarityEffects;
use App\Game\Gems\Values\ResolvedAreaGemRewardEffects;
use App\Game\Gems\Values\ResolvedAreaGemSource;

/**
 * Resolves the current rolled Map/Location Gem effects for a Game Map,
 * Location, or Character, applying the closed set of area-context
 * multipliers (normal Map/Location, Map Gem World, Location Gem World).
 */
class AreaGemEffectService
{
    /**
     * Resolve the current Gem effects for a Game Map, optionally at a specific Location.
     */
    public function resolveForGameMap(GameMap $gameMap, ?Location $location = null): ResolvedAreaGemEffects
    {
        $generatedMapType = is_null($gameMap->generated_map_type)
            ? null
            : GeneratedGemMapType::tryFrom($gameMap->generated_map_type);

        if ($generatedMapType === GeneratedGemMapType::MAP_GEM) {
            return $this->resolveMapGemWorld($gameMap);
        }

        if ($generatedMapType === GeneratedGemMapType::LOCATION_GEM) {
            return $this->resolveLocationGemWorld($gameMap);
        }

        return $this->resolveNormalMap($gameMap, $location);
    }

    /**
     * Resolve the current Gem effects for the Character's current Map/Location context.
     */
    public function resolveForCharacter(Character $character): ResolvedAreaGemEffects
    {
        $map = $character->map;

        if (is_null($map) || is_null($map->gameMap)) {
            return ResolvedAreaGemEffects::none();
        }

        $gameMap = $map->gameMap;

        if ($gameMap->isGeneratedGemMap()) {
            return $this->resolveForGameMap($gameMap);
        }

        $location = Location::where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $gameMap->id)
            ->first();

        return $this->resolveForGameMap($gameMap, $location);
    }

    /**
     * Resolve Gem effects for a nongenerated Game Map, optionally combined with a Location Gem.
     */
    private function resolveNormalMap(GameMap $gameMap, ?Location $location): ResolvedAreaGemEffects
    {
        $mapProfile = $gameMap->gemParamters;
        $mapGem = $this->rolledGem($mapProfile);

        $locationProfile = $location?->gemParamters;
        $locationGem = $this->rolledGem($locationProfile);

        if (is_null($mapGem) && is_null($locationGem)) {
            return ResolvedAreaGemEffects::none();
        }

        $sources = [];

        if (! is_null($mapGem)) {
            $sources[] = $this->buildSource(GemSourceType::MAP_GEM, $mapProfile, $mapGem, 1.0, 1.0, 1.0, $gameMap, null);
        }

        if (! is_null($locationGem)) {
            $sources[] = $this->buildSource(GemSourceType::LOCATION_GEM, $locationProfile, $locationGem, 1.0, 1.0, null, $gameMap, $location);
        }

        return new ResolvedAreaGemEffects(
            monsterEffects: $this->combineMonsterEffects($mapGem, 1.0, $locationGem, 1.0),
            rewardEffects: $this->combineRewardEffects($mapGem, 1.0, $locationGem, 1.0),
            characterPowerReduction: $this->resolveCharacterReduction($mapGem, 1.0),
            craftingSkillBonuses: $this->combineCraftingSkillBonuses($mapGem, 1.0, $locationGem, 1.0),
            rarityEffects: is_null($locationGem)
                ? $this->resolveRarity($mapGem, 1.0)
                : $this->resolveRarity($locationGem, 1.0),
            sources: $sources,
            contextType: is_null($location) ? AreaGemContext::MAP : AreaGemContext::LOCATION,
            contextLabel: is_null($location) ? $gameMap->name : $location->name,
            sourceGameMapId: $gameMap->monsterSourceGameMap()->id,
            currentGameMapId: $gameMap->id,
            currentGameMapName: $gameMap->name,
            locationId: $location?->id,
            locationName: $location?->name,
        );
    }

    /**
     * Resolve Gem effects for a generated Map Gem World.
     */
    private function resolveMapGemWorld(GameMap $gameMap): ResolvedAreaGemEffects
    {
        $profile = $gameMap->generatedMapGemParamter;
        $gem = $this->rolledGem($profile);

        if (is_null($gem)) {
            return ResolvedAreaGemEffects::none();
        }

        $sources = [
            $this->buildSource(GemSourceType::MAP_GEM, $profile, $gem, 2.0, 2.0, 1.5, $gameMap, null),
        ];

        return new ResolvedAreaGemEffects(
            monsterEffects: $this->combineMonsterEffects($gem, 2.0, null, 0.0),
            rewardEffects: $this->combineRewardEffects($gem, 2.0, null, 0.0),
            characterPowerReduction: $this->resolveCharacterReduction($gem, 1.5),
            craftingSkillBonuses: $this->combineCraftingSkillBonuses($gem, 2.0, null, 0.0),
            rarityEffects: $this->resolveRarity($gem, 2.0),
            sources: $sources,
            contextType: AreaGemContext::MAP_GEM_WORLD,
            contextLabel: $gameMap->name,
            sourceGameMapId: $gameMap->monsterSourceGameMap()->id,
            currentGameMapId: $gameMap->id,
            currentGameMapName: $gameMap->name,
        );
    }

    /**
     * Resolve Gem effects for a generated Location Gem World. The parent Map Gem still
     * contributes Monster/Character effects when the Location Gem has not been rolled yet;
     * only a fully Gem-neutral context (neither the Map nor the Location has a rolled Gem)
     * returns no effects.
     */
    private function resolveLocationGemWorld(GameMap $gameMap): ResolvedAreaGemEffects
    {
        $locationProfile = $gameMap->generatedLocationGemParamter;
        $locationGem = $this->rolledGem($locationProfile);

        $parentMap = $gameMap->generatedParentMap;
        $mapProfile = $parentMap?->gemParamters;
        $mapGem = $this->rolledGem($mapProfile);

        if (is_null($locationGem) && is_null($mapGem)) {
            return ResolvedAreaGemEffects::none();
        }

        $sources = [];

        if (! is_null($mapGem)) {
            $sources[] = $this->buildSource(GemSourceType::MAP_GEM, $mapProfile, $mapGem, 1.0, 0.0, 1.5, $parentMap, null);
        }

        if (! is_null($locationGem)) {
            $sources[] = $this->buildSource(GemSourceType::LOCATION_GEM, $locationProfile, $locationGem, 2.0, 2.0, null, $parentMap, $locationProfile->location);
        }

        return new ResolvedAreaGemEffects(
            monsterEffects: $this->combineMonsterEffects($mapGem, 1.0, $locationGem, 2.0),
            rewardEffects: $this->combineRewardEffects(null, 0.0, $locationGem, 2.0),
            characterPowerReduction: $this->resolveCharacterReduction($mapGem, 1.5),
            craftingSkillBonuses: $this->combineCraftingSkillBonuses(null, 0.0, $locationGem, 2.0),
            rarityEffects: is_null($locationGem) ? ResolvedAreaGemRarityEffects::none() : $this->resolveRarity($locationGem, 2.0),
            sources: $sources,
            contextType: AreaGemContext::LOCATION_GEM_WORLD,
            contextLabel: $gameMap->name,
            sourceGameMapId: $gameMap->monsterSourceGameMap()->id,
            currentGameMapId: $gameMap->id,
            currentGameMapName: $gameMap->name,
            locationId: $locationProfile?->location?->id,
            locationName: $locationProfile?->location?->name,
        );
    }

    /**
     * Resolve the current rolled Gem for a Map/Location Gem profile, when one exists.
     */
    private function rolledGem(GameMapGemParamter|GameLocationGemParamter|null $profile): ?Gem
    {
        if (is_null($profile) || is_null($profile->rolled_gem_id)) {
            return null;
        }

        return $profile->rolledGem;
    }

    /**
     * Combine one Map/Location Gem effect value using the closed area-context multipliers.
     */
    private function combineEffect(?float $mapValue, float $mapMultiplier, ?float $locationValue, float $locationMultiplier): float
    {
        return ($mapValue ?? 0.0) * $mapMultiplier + ($locationValue ?? 0.0) * $locationMultiplier;
    }

    /**
     * Combine the Map/Location Gem Monster combat effects and resolve the winning atonement source.
     */
    private function combineMonsterEffects(?Gem $mapGem, float $mapMultiplier, ?Gem $locationGem, float $locationMultiplier): ResolvedAreaGemMonsterEffects
    {
        return new ResolvedAreaGemMonsterEffects(
            enemyStrengthIncrease: $this->combineEffect($mapGem?->enemy_strength_increase, $mapMultiplier, $locationGem?->enemy_strength_increase, $locationMultiplier),
            enemyHealingIncrease: $this->combineEffect($mapGem?->enemy_healing_increase, $mapMultiplier, $locationGem?->enemy_healing_increase, $locationMultiplier),
            enemySpellEvasion: $this->combineEffect($mapGem?->enemy_spell_evasion, $mapMultiplier, $locationGem?->enemy_spell_evasion, $locationMultiplier),
            enemyAffixResistance: $this->combineEffect($mapGem?->enemy_affix_resistance, $mapMultiplier, $locationGem?->enemy_affix_resistance, $locationMultiplier),
            enemyEntrancingChance: $this->combineEffect($mapGem?->enemy_entrancing_chance, $mapMultiplier, $locationGem?->enemy_entrancing_chance, $locationMultiplier),
            enemyDevouringLightChance: $this->combineEffect($mapGem?->enemy_devouring_light_chance, $mapMultiplier, $locationGem?->enemy_devouring_light_chance, $locationMultiplier),
            enemyDevouringDarknessChance: $this->combineEffect($mapGem?->enemy_devouring_darkness_chance, $mapMultiplier, $locationGem?->enemy_devouring_darkness_chance, $locationMultiplier),
            enemyAmbushChance: $this->combineEffect($mapGem?->enemy_ambush_chance, $mapMultiplier, $locationGem?->enemy_ambush_chance, $locationMultiplier),
            enemyAmbushResistance: $this->combineEffect($mapGem?->enemy_ambush_resistance, $mapMultiplier, $locationGem?->enemy_ambush_resistance, $locationMultiplier),
            enemyCounterChance: $this->combineEffect($mapGem?->enemy_counter_chance, $mapMultiplier, $locationGem?->enemy_counter_chance, $locationMultiplier),
            enemyCounterResistance: $this->combineEffect($mapGem?->enemy_counter_resistance, $mapMultiplier, $locationGem?->enemy_counter_resistance, $locationMultiplier),
            atonement: $this->resolveAtonement($mapGem, $mapMultiplier, $locationGem, $locationMultiplier),
        );
    }

    /**
     * Resolve the winning Gem atonement source, preferring the Location Gem over the Map Gem.
     */
    private function resolveAtonement(?Gem $mapGem, float $mapMultiplier, ?Gem $locationGem, float $locationMultiplier): ResolvedAreaGemAtonement
    {
        if (! is_null($locationGem) && ! is_null($locationGem->monster_atonement) && ($locationGem->monster_atonement_amount ?? 0.0) > 0.0) {
            return new ResolvedAreaGemAtonement($locationGem->monster_atonement, $locationGem->monster_atonement_amount * $locationMultiplier);
        }

        if (! is_null($mapGem) && ! is_null($mapGem->monster_atonement) && ($mapGem->monster_atonement_amount ?? 0.0) > 0.0) {
            return new ResolvedAreaGemAtonement($mapGem->monster_atonement, $mapGem->monster_atonement_amount * $mapMultiplier);
        }

        return ResolvedAreaGemAtonement::none();
    }

    /**
     * Combine the Map/Location Gem additive player/reward effects.
     */
    private function combineRewardEffects(?Gem $mapGem, float $mapMultiplier, ?Gem $locationGem, float $locationMultiplier): ResolvedAreaGemRewardEffects
    {
        return new ResolvedAreaGemRewardEffects(
            characterXpBonus: $this->combineEffect($mapGem?->character_xp_bonus, $mapMultiplier, $locationGem?->character_xp_bonus, $locationMultiplier),
            characterClassRankXpBonus: $this->combineEffect($mapGem?->character_class_rank_xp_bonus, $mapMultiplier, $locationGem?->character_class_rank_xp_bonus, $locationMultiplier),
            kingdomPassiveTrainingReduction: $this->combineEffect($mapGem?->kingdom_passive_training_reduction, $mapMultiplier, $locationGem?->kingdom_passive_training_reduction, $locationMultiplier),
            goldGain: $this->combineEffect($mapGem?->gold_gain, $mapMultiplier, $locationGem?->gold_gain, $locationMultiplier),
            goldDustGain: $this->combineEffect($mapGem?->gold_dust_gain, $mapMultiplier, $locationGem?->gold_dust_gain, $locationMultiplier),
            shardsGain: $this->combineEffect($mapGem?->shards_gain, $mapMultiplier, $locationGem?->shards_gain, $locationMultiplier),
            copperCoinGain: $this->combineEffect($mapGem?->copper_coin_gain, $mapMultiplier, $locationGem?->copper_coin_gain, $locationMultiplier),
            characterClassSpecialtyXpGain: $this->combineEffect($mapGem?->character_class_specialty_xp_gain, $mapMultiplier, $locationGem?->character_class_specialty_xp_gain, $locationMultiplier),
            itemDropChanceIncrease: $this->combineEffect($mapGem?->item_drop_chance_increase, $mapMultiplier, $locationGem?->item_drop_chance_increase, $locationMultiplier),
            enemyQuestItemDropChanceIncrease: $this->combineEffect($mapGem?->enemy_quest_item_drop_chance_increase, $mapMultiplier, $locationGem?->enemy_quest_item_drop_chance_increase, $locationMultiplier),
            monsterXpIncrease: $this->combineEffect($mapGem?->monster_xp_increase, $mapMultiplier, $locationGem?->monster_xp_increase, $locationMultiplier),
            monsterGoldDropIncrease: $this->combineEffect($mapGem?->monster_gold_drop_increase, $mapMultiplier, $locationGem?->monster_gold_drop_increase, $locationMultiplier),
        );
    }

    /**
     * Combine the Map/Location Gem crafting bonus per selected GameSkill id. Each source only
     * contributes its bonus to the Skills it selected; a Skill selected by both sources adds.
     *
     * @return array<int, float>
     */
    private function combineCraftingSkillBonuses(?Gem $mapGem, float $mapMultiplier, ?Gem $locationGem, float $locationMultiplier): array
    {
        $bonuses = [];

        if (! is_null($mapGem)) {
            foreach ($mapGem->crafting_skill_ids ?? [] as $gameSkillId) {
                $bonuses[$gameSkillId] = ($bonuses[$gameSkillId] ?? 0.0) + ($mapGem->crafting_skill_bonus ?? 0.0) * $mapMultiplier;
            }
        }

        if (! is_null($locationGem)) {
            foreach ($locationGem->crafting_skill_ids ?? [] as $gameSkillId) {
                $bonuses[$gameSkillId] = ($bonuses[$gameSkillId] ?? 0.0) + ($locationGem->crafting_skill_bonus ?? 0.0) * $locationMultiplier;
            }
        }

        return $bonuses;
    }

    /**
     * Resolve the source-specific Unique/Mythic/Cosmic rarity modifiers for a single winning Gem.
     */
    private function resolveRarity(Gem $gem, float $multiplier): ResolvedAreaGemRarityEffects
    {
        return new ResolvedAreaGemRarityEffects(
            unique: ($gem->unique_item_drop_chance_increase ?? 0.0) * $multiplier,
            mythic: ($gem->mythic_item_drop_chance_increase ?? 0.0) * $multiplier,
            cosmic: ($gem->cosmic_item_drop_chance_increase ?? 0.0) * $multiplier,
        );
    }

    /**
     * Resolve the combined Character power reduction contributed by the Map Gem only.
     */
    private function resolveCharacterReduction(?Gem $mapGem, float $multiplier): float
    {
        if (is_null($mapGem)) {
            return 0.0;
        }

        return ($mapGem->character_power_reduction ?? 0.0) * $multiplier;
    }

    /**
     * Build the factual typed source metadata for one contributing Gem.
     */
    private function buildSource(
        GemSourceType $type,
        GameMapGemParamter|GameLocationGemParamter $profile,
        Gem $gem,
        float $monsterMultiplier,
        float $rewardMultiplier,
        ?float $reductionMultiplier,
        ?GameMap $gameMap,
        ?Location $location,
    ): ResolvedAreaGemSource {
        return new ResolvedAreaGemSource(
            type: $type,
            profileId: $profile->id,
            profileName: $profile->name,
            rolledGemId: $gem->id,
            rolledGemName: $gem->name,
            monsterMultiplier: $monsterMultiplier,
            rewardMultiplier: $rewardMultiplier,
            reductionMultiplier: $reductionMultiplier,
            gameMapId: $gameMap?->id,
            gameMapName: $gameMap?->name,
            locationId: $location?->id,
            locationName: $location?->name,
        );
    }
}
