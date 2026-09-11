<?php

namespace Tests\Unit\Game\Gems\Progression\Services;

use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Values\AreaGemMonsterEffect;
use App\Game\Gems\Values\AreaGemRewardEffect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
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

    public function test_location_over_map_monster_precedence_is_preserved_with_progression_overlay(): void
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

        $this->assertEqualsWithDelta(0.23, $result->monsterEffect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE), 0.0000001);
    }

    public function test_map_owned_character_power_reduction_receives_personal_negative_progression(): void
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

        $this->assertEqualsWithDelta(0.13, $result->characterPowerReduction(), 0.0000001);
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
