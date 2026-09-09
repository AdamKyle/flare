<?php

namespace Tests\Feature\Game\Monsters\Services;

use App\Flare\GemWorldGeneration\Values\GeneratedGemMapType;
use App\Game\Gems\Values\GemTypeValue;
use App\Game\Maps\Values\LocationType;
use App\Game\Monsters\Services\BuildMonsterCacheService;
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

class BuildMonsterCacheServiceTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateGem, CreateLocation, CreateMonster, RefreshDatabase;

    public function test_build_cache_creates_the_regular_monster_cache(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Regular Cache Map', 'default' => false]);
        $this->createMonster(['game_map_id' => $gameMap->id]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $this->assertTrue(Cache::has(MonsterCacheKey::MONSTERS->value));
        $this->assertNotEmpty(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data']);
    }

    public function test_build_weekly_fight_cache_creates_the_weekly_monster_cache(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Weekly Cache Map', 'default' => false]);
        $this->createMonster([
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
        ]);

        resolve(BuildMonsterCacheService::class)->buildWeeklyFightCache();

        $cache = Cache::get(MonsterCacheKey::WEEKLY_MONSTERS->value);

        $this->assertNotEmpty($cache['location-type-'.LocationType::ALCHEMY_CHURCH->value]['data']);
    }

    public function test_build_location_cache_includes_cave_of_memories_dedicated_monsters(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Cave Cache Map', 'default' => false]);
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);
        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value,
        ]);

        resolve(BuildMonsterCacheService::class)->buildLocationCache();

        $cache = Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value);
        $cached = collect($cache['location-'.$location->id]['data'])->firstWhere('id', $monster->id);

        $this->assertNotNull($cached);
    }

    public function test_build_raid_cache_creates_the_raid_monster_cache(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Raid Cache Map', 'default' => false]);
        $this->createMonster(['game_map_id' => $gameMap->id, 'is_raid_boss' => true]);

        resolve(BuildMonsterCacheService::class)->buildRaidCache();

        $this->assertNotEmpty(Cache::get(MonsterCacheKey::RAID_MONSTERS->value)[$gameMap->name]['data']);
    }

    public function test_build_celestial_cache_creates_the_celestial_cache(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Celestial Cache Map', 'default' => false]);
        $this->createMonster(['game_map_id' => $gameMap->id, 'is_celestial_entity' => true]);

        resolve(BuildMonsterCacheService::class)->buildCelestialCache();

        $this->assertNotEmpty(Cache::get(MonsterCacheKey::CELESTIALS->value)[$gameMap->name]['data']);
    }

    public function test_build_all_never_creates_the_old_special_location_monsters_cache(): void
    {
        resolve(BuildMonsterCacheService::class)->buildAll();

        $this->assertFalse(Cache::has('special-location-monsters'));
    }

    public function test_build_cache_applies_rolled_map_gem_monster_effect_to_regular_monster(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Gem Cache Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.5]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 10]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(15, $cached['str']);
    }

    public function test_build_location_cache_uses_location_gem_monster_effects_only_overriding_map_gem(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Combined Location Cache Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, ['enemy_strength_increase' => 0.1]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, ['enemy_strength_increase' => 0.2]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'str' => 10]);

        resolve(BuildMonsterCacheService::class)->buildLocationCache();

        $cached = collect(Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value)['location-'.$location->id]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(12, $cached['str']);
    }

    public function test_build_cache_doubles_monster_effect_inside_a_map_gem_world(): void
    {
        $parentMap = $this->createGameMap(['name' => 'Gem World Parent Cache Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $parentMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.1]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $parentMap->id, 'str' => 10]);

        $generatedMap = $this->createGameMap([
            'name' => 'Generated Gem World Cache Map',
            'default' => false,
            'can_traverse' => false,
            'generated_map_type' => GeneratedGemMapType::MAP_GEM->value,
            'generated_parent_game_map_id' => $parentMap->id,
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$generatedMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(12, $cached['str']);
    }

    public function test_raid_boss_cache_is_never_gem_affected(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Raid Gem Neutral Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $raidBoss = $this->createMonster(['game_map_id' => $gameMap->id, 'is_raid_boss' => true, 'str' => 10]);

        resolve(BuildMonsterCacheService::class)->buildRaidCache();

        $cached = collect(Cache::get(MonsterCacheKey::RAID_MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $raidBoss->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_celestial_cache_is_never_gem_affected(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Celestial Gem Neutral Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $celestial = $this->createMonster(['game_map_id' => $gameMap->id, 'is_celestial_entity' => true, 'str' => 10]);

        resolve(BuildMonsterCacheService::class)->buildCelestialCache();

        $cached = collect(Cache::get(MonsterCacheKey::CELESTIALS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $celestial->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_weekly_fight_cache_is_never_gem_affected(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Weekly Gem Neutral Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $weeklyMonster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH->value,
            'str' => 10,
        ]);

        resolve(BuildMonsterCacheService::class)->buildWeeklyFightCache();

        $cached = collect(Cache::get(MonsterCacheKey::WEEKLY_MONSTERS->value)['location-type-'.LocationType::ALCHEMY_CHURCH->value]['data'])
            ->firstWhere('id', $weeklyMonster->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_cave_of_memories_location_cache_is_never_gem_affected(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Cave Gem Neutral Map', 'default' => false]);
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

        resolve(BuildMonsterCacheService::class)->buildLocationCache();

        $cached = collect(Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value)['location-'.$location->id]['data'])
            ->firstWhere('id', $caveMonster->id);

        $this->assertSame(10, $cached['str']);
    }

    public function test_location_cache_keys_two_same_type_locations_separately(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Same Type Locations Map', 'default' => false]);
        $this->createMonster(['game_map_id' => $gameMap->id]);

        $locationOne = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => LocationType::SPECIAL->value]);
        $profileOne = $this->createGameLocationGemParamter(['location_id' => $locationOne->id]);
        $gemOne = $this->createLocationGeneratedGem($profileOne, ['enemy_strength_increase' => 0.1]);
        $profileOne->update(['rolled_gem_id' => $gemOne->id]);

        $locationTwo = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => LocationType::SPECIAL->value]);
        $profileTwo = $this->createGameLocationGemParamter(['location_id' => $locationTwo->id]);
        $gemTwo = $this->createLocationGeneratedGem($profileTwo, ['enemy_strength_increase' => 0.2]);
        $profileTwo->update(['rolled_gem_id' => $gemTwo->id]);

        resolve(BuildMonsterCacheService::class)->buildLocationCache();

        $cache = Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value);

        $this->assertArrayHasKey('location-'.$locationOne->id, $cache);
        $this->assertArrayHasKey('location-'.$locationTwo->id, $cache);
    }

    public function test_invalidate_gem_affected_caches_removes_only_monster_and_location_caches(): void
    {
        resolve(BuildMonsterCacheService::class)->buildAll();

        resolve(BuildMonsterCacheService::class)->invalidateGemAffectedCaches();

        $this->assertFalse(Cache::has(MonsterCacheKey::MONSTERS->value));
        $this->assertFalse(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
        $this->assertTrue(Cache::has(MonsterCacheKey::WEEKLY_MONSTERS->value));
        $this->assertTrue(Cache::has(MonsterCacheKey::RAID_MONSTERS->value));
        $this->assertTrue(Cache::has(MonsterCacheKey::CELESTIALS->value));
    }

    public function test_enemy_strength_increase_scales_the_health_range(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Health Range Scale Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.5]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'health_range' => '100-200']);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame('150-300', $cached['health_range']);
    }

    public function test_enemy_strength_increase_scales_the_attack_range(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Attack Range Scale Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_strength_increase' => 0.5]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'attack_range' => '10-20']);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame('15-30', $cached['attack_range']);
    }

    public function test_enemy_healing_increase_applies_separately_from_enemy_strength_increase(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Healing Increase Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, [
            'enemy_strength_increase' => 0.5,
            'enemy_healing_increase' => 0.1,
        ]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'healing_percentage' => 0.2]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertEqualsWithDelta(0.22, $cached['max_healing'], 0.0001);
    }

    public function test_spell_evasion_adds_the_gem_effect_and_caps_at_zero_point_nine_five(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Spell Evasion Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_spell_evasion' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'spell_evasion' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(0.95, $cached['spell_evasion']);
    }

    public function test_affix_resistance_adds_the_gem_effect_and_caps_at_zero_point_nine_five(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Affix Resistance Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_affix_resistance' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'affix_resistance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(0.95, $cached['affix_resistance']);
    }

    public function test_entrancing_chance_adds_the_gem_effect_and_caps_at_zero_point_nine_five(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Entrancing Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_entrancing_chance' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'entrancing_chance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(0.95, $cached['entrancing_chance']);
    }

    public function test_devouring_light_chance_caps_at_zero_point_seven_five(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Devouring Light Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_devouring_light_chance' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'devouring_light_chance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(0.75, $cached['devouring_light_chance']);
    }

    public function test_devouring_darkness_chance_caps_at_zero_point_seven_five(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Devouring Darkness Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_devouring_darkness_chance' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'devouring_darkness_chance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(0.75, $cached['devouring_darkness_chance']);
    }

    public function test_ambush_chance_caps_at_one(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Ambush Chance Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_ambush_chance' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'ambush_chance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(1.0, $cached['ambush_chance']);
    }

    public function test_ambush_resistance_caps_at_one(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Ambush Resistance Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_ambush_resistance' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'ambush_resistance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(1.0, $cached['ambush_resistance_chance']);
    }

    public function test_counter_chance_caps_at_one(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Counter Chance Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_counter_chance' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'counter_chance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(1.0, $cached['counter_chance']);
    }

    public function test_counter_resistance_caps_at_one(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Counter Resistance Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_counter_resistance' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'counter_resistance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(1.0, $cached['counter_resistance_chance']);
    }

    public function test_effective_quest_item_drop_chance_caps_at_one(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Quest Item Drop Cap Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['enemy_quest_item_drop_chance_increase' => 0.9]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'quest_item_drop_chance' => 0.5]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(1.0, $cached['quest_item_drop_chance']);
    }

    public function test_monster_xp_increase_applies_exactly_once(): void
    {
        $gameMap = $this->createGameMap(['name' => 'XP Increase Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['monster_xp_increase' => 0.5]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'xp' => 100]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(150, $cached['xp']);
    }

    public function test_monster_gold_drop_increase_applies_exactly_once(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Gold Increase Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, ['monster_gold_drop_increase' => 0.5]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster(['game_map_id' => $gameMap->id, 'gold' => 100]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(150, $cached['gold']);
    }

    public function test_map_gem_atonement_is_used_when_the_monster_has_no_own_atonement(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Map Atonement Map', 'default' => false]);
        $profile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $gem = $this->createMapGeneratedGem($profile, [
            'monster_atonement' => GemTypeValue::FIRE,
            'monster_atonement_amount' => 0.3,
        ]);
        $profile->update(['rolled_gem_id' => $gem->id]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'fire_atonement' => 0,
            'ice_atonement' => 0,
            'water_atonement' => 0,
        ]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cached = collect(Cache::get(MonsterCacheKey::MONSTERS->value)[$gameMap->name]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(0.3, $cached['fire_atonement']);
    }

    public function test_location_gem_atonement_overrides_map_gem_atonement(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Location Atonement Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, [
            'monster_atonement' => GemTypeValue::FIRE,
            'monster_atonement_amount' => 0.3,
        ]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, [
            'monster_atonement' => GemTypeValue::ICE,
            'monster_atonement_amount' => 0.4,
        ]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'fire_atonement' => 0,
            'ice_atonement' => 0,
            'water_atonement' => 0,
        ]);

        resolve(BuildMonsterCacheService::class)->buildLocationCache();

        $cached = collect(Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value)['location-'.$location->id]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(0.4, $cached['ice_atonement']);
        $this->assertSame(0.0, $cached['fire_atonement']);
    }

    public function test_persisted_monster_atonement_overrides_map_and_location_gem_atonement(): void
    {
        $gameMap = $this->createGameMap(['name' => 'Persisted Atonement Map', 'default' => false]);
        $mapProfile = $this->createGameMapGemParamter(['game_map_id' => $gameMap->id]);
        $mapGem = $this->createMapGeneratedGem($mapProfile, [
            'monster_atonement' => GemTypeValue::FIRE,
            'monster_atonement_amount' => 0.3,
        ]);
        $mapProfile->update(['rolled_gem_id' => $mapGem->id]);

        $location = $this->createLocation(['game_map_id' => $gameMap->id, 'type' => null]);
        $locationProfile = $this->createGameLocationGemParamter(['location_id' => $location->id]);
        $locationGem = $this->createLocationGeneratedGem($locationProfile, [
            'monster_atonement' => GemTypeValue::ICE,
            'monster_atonement_amount' => 0.4,
        ]);
        $locationProfile->update(['rolled_gem_id' => $locationGem->id]);

        $monster = $this->createMonster([
            'game_map_id' => $gameMap->id,
            'fire_atonement' => 0,
            'ice_atonement' => 0,
            'water_atonement' => 0.6,
        ]);

        resolve(BuildMonsterCacheService::class)->buildLocationCache();

        $cached = collect(Cache::get(MonsterCacheKey::LOCATION_MONSTERS->value)['location-'.$location->id]['data'])
            ->firstWhere('id', $monster->id);

        $this->assertSame(0.6, $cached['water_atonement']);
        $this->assertSame(0.0, $cached['ice_atonement']);
    }

    public function test_event_map_cache_retains_the_regular_and_easier_structure(): void
    {
        $surface = $this->createGameMap(['name' => 'Surface', 'default' => true]);
        $this->createMonster(['game_map_id' => $surface->id]);

        $eventMap = $this->createGameMap([
            'name' => 'Event Map',
            'default' => false,
            'only_during_event_type' => 1,
        ]);
        $this->createMonster(['game_map_id' => $eventMap->id]);

        resolve(BuildMonsterCacheService::class)->buildCache();

        $cache = Cache::get(MonsterCacheKey::MONSTERS->value)[$eventMap->name];

        $this->assertArrayHasKey('regular', $cache);
        $this->assertArrayHasKey('easier', $cache);
        $this->assertNotEmpty($cache['regular']['data']);
        $this->assertNotEmpty($cache['easier']['data']);
    }
}
