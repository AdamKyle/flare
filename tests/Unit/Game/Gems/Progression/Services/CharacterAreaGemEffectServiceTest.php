<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Values\AreaGemMonsterEffect;
use App\Game\Gems\Values\AreaGemRewardEffect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\GemProgression\GemWorldRewardTestFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGameLocationGemProgression;
use Tests\Traits\CreateCharacterGameMapGemProgression;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameLocationGemProgression;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameMapGemProgression;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateLocation;

class CharacterAreaGemEffectServiceTest extends TestCase
{
    use CreateCharacterGameLocationGemProgression,
        CreateCharacterGameMapGemProgression,
        CreateGameLocationGemParamter,
        CreateGameLocationGemProgression,
        CreateGameMapGemParamter,
        CreateGameMapGemProgression,
        CreateGem,
        CreateLocation,
        RefreshDatabase;

    private CharacterAreaGemEffectService $characterAreaGemEffectService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->characterAreaGemEffectService = new CharacterAreaGemEffectService(
            new AreaGemEffectService,
            new GemProgressionEffectService,
        );
    }

    public function test_no_progression_rows_leaves_the_rolled_effect_unchanged(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['gold_gain' => 0.05]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $result = $this->characterAreaGemEffectService->resolveForCharacter($character->refresh());

        $this->assertEqualsWithDelta(0.05, $result->rewardEffect(AreaGemRewardEffect::GOLD_GAIN), 0.0000001);
    }

    public function test_global_and_personal_max_base_progression_resolves_rolled_five_percent_to_fifteen_percent(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['gold_gain' => 0.05]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $this->createGameMapGemProgression(['game_map_gem_paramter_id' => $profile->id, 'level' => 100, 'xp' => 0]);
        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 100,
            'xp' => 0,
        ]);

        $result = $this->characterAreaGemEffectService->resolveForCharacter($character->refresh());

        $this->assertEqualsWithDelta(0.15, $result->rewardEffect(AreaGemRewardEffect::GOLD_GAIN), 0.0000001);
    }

    public function test_normal_map_location_precedence_does_not_receive_personal_negative_progression(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['enemy_strength_increase' => 0.10]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => null,
        ]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.20]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $this->createCharacterGameLocationGemProgression([
            'character_id' => $character->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);
        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $mapProfile->id,
            'level' => 1000,
            'xp' => 0,
        ]);

        $result = $this->characterAreaGemEffectService->resolveForCharacter($character->refresh());

        $this->assertEqualsWithDelta(0.20, $result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 0.0000001);
    }

    public function test_map_owned_character_power_reduction_on_a_normal_map_does_not_receive_personal_negative_progression(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['character_power_reduction' => 0.10]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $mapProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $result = $this->characterAreaGemEffectService->resolveForCharacter($character->refresh());

        $this->assertEqualsWithDelta(0.10, $result->characterPowerReduction(), 0.0000001);
    }

    public function test_generated_map_gem_world_monster_effect_receives_personal_negative_progression(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedMapGemWorldCharacter();

        $graph->mapProfile->rolledGem()->update(['enemy_strength_increase' => 0.10]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $graph->character->id,
            'game_map_gem_paramter_id' => $graph->mapProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $result = $this->characterAreaGemEffectService->resolveForCharacter($graph->character->refresh());

        // Rolled 0.10 x the established 2.0 Map Gem World monster multiplier = 0.20, plus the personal negative bonus (0.03 at level 200).
        $this->assertEqualsWithDelta(0.23, $result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 0.0000001);
    }

    public function test_generated_location_gem_world_monster_effect_receives_personal_negative_progression(): void
    {
        $graph = (new GemWorldRewardTestFactory)->buildGeneratedLocationGemWorldCharacter();

        $graph->locationProfile->rolledGem()->update(['enemy_strength_increase' => 0.20]);

        $this->createCharacterGameLocationGemProgression([
            'character_id' => $graph->character->id,
            'game_location_gem_paramter_id' => $graph->locationProfile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $result = $this->characterAreaGemEffectService->resolveForCharacter($graph->character->refresh());

        // Rolled 0.20 x the established 2.0 Location Gem World monster multiplier = 0.40, plus the personal negative bonus (0.03 at level 200).
        $this->assertEqualsWithDelta(0.43, $result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 0.0000001);
    }

    public function test_location_source_does_not_gain_character_power_reduction(): void
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
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['gold_gain' => 0.05]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $this->createCharacterGameLocationGemProgression([
            'character_id' => $character->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
            'level' => 1000,
            'xp' => 0,
        ]);

        $result = $this->characterAreaGemEffectService->resolveForCharacter($character->refresh());

        $this->assertSame(0.0, $result->characterPowerReduction());
    }
}
