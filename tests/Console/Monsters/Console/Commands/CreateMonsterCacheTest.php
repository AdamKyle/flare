<?php

namespace Tests\Console\Monsters\Console\Commands;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\Maps\Values\LocationType;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class CreateMonsterCacheTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateGem, CreateLocation, CreateMonster, RefreshDatabase;

    public function test_command_creates_the_monsters_cache(): void
    {
        $this->createGameMap(['name' => 'Command Regular Map', 'default' => false]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $this->assertTrue(Cache::has(MonsterCacheKey::MONSTERS->value));
    }

    public function test_command_creates_the_location_monsters_cache(): void
    {
        $this->createGameMap(['name' => 'Command Location Map', 'default' => false]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $this->assertTrue(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
    }

    public function test_command_creates_the_weekly_monsters_cache(): void
    {
        $this->createGameMap(['name' => 'Command Weekly Map', 'default' => false]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $this->assertTrue(Cache::has(MonsterCacheKey::WEEKLY_MONSTERS->value));
    }

    public function test_command_creates_the_raid_monsters_cache(): void
    {
        $this->createGameMap(['name' => 'Command Raid Map', 'default' => false]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $this->assertTrue(Cache::has(MonsterCacheKey::RAID_MONSTERS->value));
    }

    public function test_command_creates_the_celestials_cache(): void
    {
        $this->createGameMap(['name' => 'Command Celestial Map', 'default' => false]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $this->assertTrue(Cache::has(MonsterCacheKey::CELESTIALS->value));
    }

    public function test_command_does_not_create_the_old_special_location_monsters_cache(): void
    {
        $this->createGameMap(['name' => 'Command No Special Map', 'default' => false]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $this->assertFalse(Cache::has('special-location-monsters'));
    }

    public function test_normal_map_rolled_gem_applies_at_normal_multiplier(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Command Rolled Gem Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.5]);
        $profile->update(['rolled_gem_id' => $gem->id]);
        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 10]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(15, $cached['str']);
    }

    public function test_location_gem_overrides_map_gem_monster_effects_in_a_normal_location(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Command Location Gem Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['enemy_strength_increase' => 0.1]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.2]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 10]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value)['location-'.$location->id]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(12, $cached['str']);
        $this->assertSame($monster->id, $cached['id']);
    }

    public function test_map_gem_world_doubles_map_gem_monster_effect(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Command Parent Gem World Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.1]);
        $profile->update(['rolled_gem_id' => $gem->id]);
        $monster = $this->createMonster(['game_map_id' => $parentMap->id, 'str' => 10]);

        $generatedMap = $this->createGameMap([
            'name' => 'Command Generated Gem World Map',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$generatedMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(12, $cached['str']);
    }

    public function test_location_gem_world_doubles_location_gem_monster_effects_without_adding_parent_map_gem_monster_effects(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Command Parent Location Gem World Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['enemy_strength_increase' => 0.1]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $parentMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.2]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $monster = $this->createMonster(['game_map_id' => $parentMap->id, 'str' => 10]);

        $generatedMap = $this->createGameMap([
            'name' => 'Command Generated Location Gem World Map',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::LOCATION_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_location_gem_paramter_id' => $locationProfile->id,
        ]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$generatedMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(14, $cached['str']);
    }

    public function test_raid_monster_remains_unaffected_by_a_rolled_gem(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Command Raid Unaffected Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);
        $raidMonster = $this->createMonster(['game_map_id' => $gameMap->id, 'is_raid_monster' => true, 'str' => 10]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::RAID_MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $raidMonster->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_raid_boss_remains_unaffected_by_a_rolled_gem(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Command Boss Unaffected Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);
        $raidBoss = $this->createMonster(['game_map_id' => $gameMap->id, 'is_raid_boss' => true, 'str' => 10]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::RAID_MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $raidBoss->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_weekly_monster_remains_unaffected_by_a_rolled_gem(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Command Weekly Unaffected Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);
        $weeklyMonster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
            'str' => 10,
        ]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::WEEKLY_MONSTERS->value)['location-type-'.LocationType::ALCHEMY_CHURCH->value]['data'])
            ->firstWhere('id', $weeklyMonster->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_cave_of_memories_monster_remains_unaffected_by_a_rolled_gem(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Command Cave Unaffected Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);
        $caveMonster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
            'str' => 10,
        ]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value)['location-'.$location->id]['data'])
            ->firstWhere('id', $caveMonster->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_celestial_remains_unaffected_by_a_rolled_gem(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Command Celestial Unaffected Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);
        $celestial = $this->createMonster(['game_map_id' => $gameMap->id, 'is_celestial_entity' => true, 'str' => 10]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::CELESTIALS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $celestial->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_location_cache_reuses_the_maps_regular_persisted_monster(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Command Reused Monster Map', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.1]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $regularMonster = $this->createMonster(['game_map_id' => $gameMap->id]);

        $this->artisan('generate:monster-cache')->assertExitCode(0);

        $cached = collect(Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value)['location-'.$location->id]['data'])
            ->firstWhere('id', $regularMonster->id);

        $this->assertNotNull($cached);
    }
}
