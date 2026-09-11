<?php

namespace Tests\Feature\Game\Monsters\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\Maps\Values\LocationType;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterGameMapGemProgression;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class MonsterListServiceTest extends TestCase
{
    use CreateCharacterGameMapGemProgression, CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateGem, CreateLocation, CreateMonster, RefreshDatabase;

    public function test_normal_map_resolves_the_regular_monsters_cache(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;
        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $list = resolve(MonsterListService::class)->getMonstersForCharacterAsList($character->refresh());

        $this->assertTrue(collect($list)->contains('id', $monster->id));
    }

    public function test_gem_bearing_location_resolves_its_own_location_cache(): void
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
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.1]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $this->createMonster(['game_map_id' => $gameMap->id]);

        resolve(MonsterListService::class)->getMonstersForCharacterAsList($character->refresh());

        $this->assertTrue(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
        $this->assertArrayHasKey('location-'.$location->id, Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value));
    }

    public function test_location_with_no_rolled_gem_falls_back_to_the_normal_map_cache(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $this->createLocation([
            'game_map_id' => $gameMap->id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => null,
        ]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $list = resolve(MonsterListService::class)->getMonstersForCharacterAsList($character->refresh());

        $this->assertTrue(collect($list)->contains('id', $monster->id));
    }

    public function test_weekly_fight_location_resolves_the_weekly_monsters_cache(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $this->createLocation([
            'game_map_id' => $gameMap->id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);

        $weeklyMonster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);

        $list = resolve(MonsterListService::class)->getMonstersForCharacterAsList($character->refresh());

        $this->assertTrue(collect($list)->contains('id', $weeklyMonster->id));
    }

    public function test_two_locations_with_the_same_location_type_resolve_separate_location_gem_caches(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $locationOne = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => LocationType::SPECIAL->value]);
        $profileOne = $this->createGameLocationGemParamter(['location_id' => $locationOne->id]);
        $gemOne = $this->createLocationGeneratedGem($profileOne, ['enemy_strength_increase' => 0.1]);
        $profileOne->update(['rolled_gem_id' => $gemOne->id]);

        $locationTwo = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => LocationType::SPECIAL->value]);
        $profileTwo = $this->createGameLocationGemParamter(['location_id' => $locationTwo->id]);
        $gemTwo = $this->createLocationGeneratedGem($profileTwo, ['enemy_strength_increase' => 0.2]);
        $profileTwo->update(['rolled_gem_id' => $gemTwo->id]);

        $this->createMonster(['game_map_id' => $gameMap->id]);

        resolve(MonsterListService::class)->getMonstersForCharacterAsList($character->refresh());

        $cache = Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value);

        $this->assertArrayHasKey('location-'.$locationOne->id, $cache);
        $this->assertArrayHasKey('location-'.$locationTwo->id, $cache);
    }

    public function test_map_gem_world_resolves_the_generated_maps_own_cache(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $parentMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.1]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $parentMap->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Map Gem World List',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $character->map->update(['game_map_id' => $generatedMap->id]);

        $list = resolve(MonsterListService::class)->getMonstersForCharacterAsList($character->refresh());

        $this->assertTrue(collect($list)->contains('id', $monster->id));
    }

    public function test_location_gem_world_resolves_the_generated_maps_own_cache(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $parentMap = $character->map->gameMap;

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.1]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $monster = $this->createMonster(['game_map_id' => $parentMap->id]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Location Gem World List',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
        ]);

        $character->map->update(['game_map_id' => $generatedMap->id]);

        $list = resolve(MonsterListService::class)->getMonstersForCharacterAsList($character->refresh());

        $this->assertTrue(collect($list)->contains('id', $monster->id));
    }

    public function test_personal_negative_progression_makes_the_effective_monster_stronger(): void
    {
        Cache::flush();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.10]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 100, 'damage_stat' => 'str']);

        $effectiveMonster = resolve(MonsterListService::class)->getMonsterForFight($character->refresh(), $monster->id);

        $this->assertSame(113, $effectiveMonster['str']);
    }

    public function test_weekly_fight_monsters_remain_gem_neutral_even_with_personal_progression(): void
    {
        Cache::flush();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $gameMap = $character->map->gameMap;

        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.10]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $this->createCharacterGameMapGemProgression([
            'character_id' => $character->id,
            'game_map_gem_paramter_id' => $profile->id,
            'level' => 200,
            'xp' => 0,
        ]);

        $this->createLocation([
            'game_map_id' => $gameMap->id,
            'x' => $character->map->character_position_x,
            'y' => $character->map->character_position_y,
            'type' => LocationType::ALCHEMY_CHURCH->value,
        ]);

        $weeklyMonster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
            'str' => 100,
            'damage_stat' => 'str',
        ]);

        $effectiveMonster = resolve(MonsterListService::class)->getMonsterForFight($character->refresh(), $weeklyMonster->id);

        $this->assertSame(100, $effectiveMonster['str']);
    }
}
