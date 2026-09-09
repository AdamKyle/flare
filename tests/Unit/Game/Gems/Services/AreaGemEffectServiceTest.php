<?php

namespace Tests\Unit\Game\Gems\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Values\AreaGemMonsterEffect;
use App\Game\Gems\Values\AreaGemRewardEffect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateLocation;

class AreaGemEffectServiceTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateGem, CreateLocation, RefreshDatabase;

    private AreaGemEffectService $areaGemEffectService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->areaGemEffectService = new AreaGemEffectService;
    }

    public function test_resolve_for_game_map_returns_no_effects_when_map_has_no_rolled_gem(): void
    {
        $gameMap = $this->createGameMap(['name' => 'No Gem Map', 'default' => false]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap);

        $this->assertFalse($result->hasAnyEffects());
    }

    public function test_resolve_for_game_map_applies_map_gem_monster_effect_at_normal_multiplier(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Normal Monster Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.15]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh());

        $this->assertSame(0.15, round($result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 2));
    }

    public function test_resolve_for_game_map_applies_map_gem_reward_effect_at_normal_multiplier(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Normal Reward Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['gold_gain' => 0.2]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh());

        $this->assertSame(0.2, round($result->rewardEffect(AreaGemRewardEffect::GOLD_GAIN), 2));
    }

    public function test_resolve_for_game_map_applies_map_gem_character_reduction_at_normal_multiplier(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Normal Reduction Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['character_power_reduction' => 0.1]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh());

        $this->assertSame(0.1, round($result->characterPowerReduction(), 2));
    }

    public function test_resolve_for_game_map_with_rolled_location_gem_uses_location_gem_monster_effect_only(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Combined Monster Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['enemy_strength_increase' => 0.1]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.2]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh(), $location->refresh());

        // Only the Location Gem's Monster effect applies; the Map Gem's 0.1 does not stack.
        $this->assertSame(0.2, round($result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 2));
    }

    public function test_resolve_for_game_map_with_rolled_location_gem_adds_map_and_location_reward_effects(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Combined Reward Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['gold_gain' => 0.1, 'kingdom_passive_training_reduction' => 0.05]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['gold_gain' => 0.2, 'kingdom_passive_training_reduction' => 0.08]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh(), $location->refresh());

        $this->assertSame(0.3, round($result->rewardEffect(AreaGemRewardEffect::GOLD_GAIN), 2));
        $this->assertSame(0.13, round($result->rewardEffect(AreaGemRewardEffect::KINGDOM_PASSIVE_TRAINING_REDUCTION), 2));
    }

    public function test_resolve_for_game_map_without_rolled_location_gem_falls_back_to_map_gem_monster_effect(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Fallback Monster Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['enemy_strength_increase' => 0.1]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh(), $location->refresh());

        $this->assertSame(0.1, round($result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 2));
    }

    public function test_resolve_for_game_map_location_context_reduction_uses_only_map_gem(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Location Reduction Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['character_power_reduction' => 0.1]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, []);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh(), $location->refresh());

        $this->assertSame(0.1, round($result->characterPowerReduction(), 2));
    }

    public function test_resolve_for_map_gem_world_doubles_monster_effects(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Gem World Monster', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.15]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Map Gem World Monster',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        $this->assertSame(0.3, round($result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 2));
    }

    public function test_resolve_for_map_gem_world_doubles_reward_effects(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Gem World Reward', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['gold_gain' => 0.2]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Map Gem World Reward',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        $this->assertSame(0.4, round($result->rewardEffect(AreaGemRewardEffect::GOLD_GAIN), 2));
    }

    public function test_resolve_for_map_gem_world_applies_one_point_five_reduction(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Gem World Reduction', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['character_power_reduction' => 0.1]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Map Gem World Reduction',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        $this->assertSame(0.15, round($result->characterPowerReduction(), 2));
    }

    public function test_resolve_for_location_gem_world_with_rolled_location_gem_uses_location_gem_monster_effect_at_double_only(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Location Gem World Monster', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['enemy_strength_increase' => 0.15]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.18]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Location Gem World Monster',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        // Only the Location Gem's Monster effect applies at x2; the parent Map Gem's 0.15 does not stack.
        $this->assertSame(0.36, round($result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 2));
    }

    public function test_resolve_for_location_gem_world_rewards_use_location_gem_only(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Location Gem World Reward', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['gold_gain' => 0.2]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['gold_gain' => 0.3]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Location Gem World Reward',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        $this->assertSame(0.6, round($result->rewardEffect(AreaGemRewardEffect::GOLD_GAIN), 2));
    }

    public function test_resolve_for_location_gem_world_reduction_uses_parent_map_at_one_point_five(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Location Gem World Reduction', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['character_power_reduction' => 0.1]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, []);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Location Gem World Reduction',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        $this->assertSame(0.15, round($result->characterPowerReduction(), 2));
    }

    public function test_resolve_for_location_gem_world_uses_parent_map_monster_and_reduction_when_location_gem_is_unrolled(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Missing Location Roll', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, [
            'enemy_strength_increase' => 0.2,
            'character_power_reduction' => 0.1,
            'gold_gain' => 0.3,
        ]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Location Gem World Missing Roll',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        $this->assertTrue($result->hasAnyEffects());
        $this->assertSame(0.2, round($result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 2));
        $this->assertSame(0.15, round($result->characterPowerReduction(), 2));
        $this->assertSame(0.0, $result->rewardEffect(AreaGemRewardEffect::GOLD_GAIN));
    }

    public function test_normal_map_rarity_uses_map_gem_at_normal_multiplier(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Normal Map Rarity', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, [
            'unique_item_drop_chance_increase' => 0.1,
            'mythic_item_drop_chance_increase' => 0.2,
            'cosmic_item_drop_chance_increase' => 0.3,
        ]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh());

        $this->assertSame(0.1, round($result->rarityEffects()->unique(), 2));
        $this->assertSame(0.2, round($result->rarityEffects()->mythic(), 2));
        $this->assertSame(0.3, round($result->rarityEffects()->cosmic(), 2));
    }

    public function test_normal_location_with_rolled_gem_adds_map_and_location_rarity(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Normal Location Rarity', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['unique_item_drop_chance_increase' => 0.9]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['unique_item_drop_chance_increase' => 0.15]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh(), $location->refresh());

        $this->assertSame(1.05, round($result->rarityEffects()->unique(), 2));
    }

    public function test_normal_location_without_rolled_gem_falls_back_to_map_gem_rarity(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Fallback Location Rarity', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['mythic_item_drop_chance_increase' => 0.25]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh(), $location->refresh());

        $this->assertSame(0.25, round($result->rarityEffects()->mythic(), 2));
    }

    public function test_map_gem_world_rarity_doubles_map_gem_rarity(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Gem World Rarity', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['cosmic_item_drop_chance_increase' => 0.1]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Map Gem World Rarity',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        $this->assertSame(0.2, round($result->rarityEffects()->cosmic(), 2));
    }

    public function test_location_gem_world_rarity_doubles_location_gem_rarity_only(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Parent Map Location Gem World Rarity', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['unique_item_drop_chance_increase' => 0.9]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['unique_item_drop_chance_increase' => 0.1]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Location Gem World Rarity',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
        ]);

        $result = $this->areaGemEffectService->resolveForGameMap($generatedMap->refresh());

        $this->assertSame(0.2, round($result->rarityEffects()->unique(), 2));
    }

    public function test_resolve_for_character_finds_rolled_location_gem_by_coordinates_without_requiring_a_location_type(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => null,
        ]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.2]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $result = $this->areaGemEffectService->resolveForCharacter($character->refresh());

        $this->assertSame(0.2, round($result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 2));
    }

    public function test_resolve_source_metadata_includes_profile_and_rolled_gem_ids(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Metadata Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.15]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $result = $this->areaGemEffectService->resolveForGameMap($gameMap->refresh());

        $source = $result->sources()[0];

        $this->assertSame($profile->id, $source->profileId());
        $this->assertSame($gem->id, $source->rolledGemId());
        $this->assertSame(1.0, $source->monsterMultiplier());
    }
}
