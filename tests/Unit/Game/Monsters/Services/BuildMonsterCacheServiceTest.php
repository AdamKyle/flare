<?php

namespace Tests\Unit\Game\Monsters\Services;

use App\Flare\Values\MapNameValue;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use Facades\App\Game\Maps\Calculations\LocationBasedEnemyDropChanceBonus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class BuildMonsterCacheServiceTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateMonster, RefreshDatabase;

    private ?BuildMonsterCacheService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(BuildMonsterCacheService::class);

        Cache::flush();
    }

    public function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_builds_regular_map_dataset()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $monsterA = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'str' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'str' => 12,
            'health_range' => '12-24',
            'attack_range' => '2-4',
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');

        $this->assertArrayHasKey($surface->name, $cache);
        $data = $cache[$surface->name]['data'];
        $this->assertCount(2, $data);

        $first = collect($data)->firstWhere('id', $monsterA->id);
        $this->assertSame(10, $first['str']);
    }

    public function test_event_map_splits_regular_and_easier_lists()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $event = $this->createGameMap([
            'name' => 'Autumn Event',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => 1,
        ]);

        $surfaceMonster = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $eventMonster = $this->createMonster([
            'game_map_id' => $event->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');
        $entry = $cache[$event->name];

        $regularIds = collect($entry['regular']['data'])->pluck('id')->all();
        $easierIds = collect($entry['easier']['data'])->pluck('id')->all();

        $this->assertContains($eventMonster->id, $regularIds);
        $this->assertContains($surfaceMonster->id, $easierIds);
    }

    public function test_raid_cache_includes_bosses_and_adds_location_entries()
    {
        $map = $this->createGameMap([
            'name' => 'Forest',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $add = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => true,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $boss = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => true,
            'only_for_location_type' => null,
        ]);

        $regular = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $location = $this->createLocation([
            'name' => 'Thicket',
            'game_map_id' => $map->id,
            'enemy_strength_increase' => 1,
            'type' => null,
        ]);

        $this->service->buildRaidCache();

        $cache = Cache::get('raid-monsters');
        $raidIds = collect($cache[$map->name]['data'])->pluck('id')->all();

        $this->assertContains($add->id, $raidIds);
        $this->assertContains($boss->id, $raidIds);

        $locIds = collect($cache[$location->name]['data'])->pluck('id')->all();
        $this->assertContains($regular->id, $locIds);
    }

    public function test_special_location_monster_list_for_types()
    {
        $map = $this->createGameMap([
            'name' => 'Caves',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $this->createLocation([
            'name' => 'Crystal Cavern',
            'game_map_id' => $map->id,
            'type' => 10,
        ]);

        $this->createLocation([
            'name' => 'Empty Grotto',
            'game_map_id' => $map->id,
            'type' => 20,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => 10,
        ]);

        $this->service->buildSpecialLocationMonsterList();

        $cache = Cache::get('special-location-monsters');
        $idsA = collect($cache['location-type-10']['data'])->pluck('id')->all();
        $this->assertContains($monster->id, $idsA);
        $this->assertCount(0, $cache['location-type-20']['data']);
    }

    public function test_celestial_cache_excludes_by_location_type()
    {
        $map = $this->createGameMap([
            'name' => 'Sky',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $included = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => true,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $excluded = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => true,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => 99,
        ]);

        $nonCelestial = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $this->service->buildCelesetialCache();

        $cache = Cache::get('celestials');
        $ids = collect($cache[$map->name]['data'])->pluck('id')->all();

        $this->assertContains($included->id, $ids);
        $this->assertNotContains($excluded->id, $ids);
        $this->assertNotContains($nonCelestial->id, $ids);
    }

    public function test_location_flat_increase_applies_to_integer_stats()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $location = $this->createLocation([
            'name' => 'Ruins',
            'game_map_id' => $surface->id,
            'enemy_strength_increase' => 1,
            'type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'str' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');
        $row = collect($cache[$location->name]['data'])->firstWhere('id', $monster->id);

        $this->assertSame(11, $row['str']);
    }

    public function test_location_flat_increase_shifts_ranges()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $location = $this->createLocation([
            'name' => 'Ruins',
            'game_map_id' => $surface->id,
            'enemy_strength_increase' => 1,
            'type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $surface->id,
            'health_range' => '10-20',
            'attack_range' => '1-3',
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');
        $row = collect($cache[$location->name]['data'])->firstWhere('id', $monster->id);

        $this->assertSame('11-21', $row['health_range']);
        $this->assertSame('2-4', $row['attack_range']);
    }

    public function test_regular_map_ignores_map_enemy_stat_bonus()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'str' => 10,
            'spell_evasion' => 0.95,
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');
        $row = collect($cache[$map->name]['data'])->firstWhere('id', $monster->id);

        $this->assertSame(10, $row['str']);
        $this->assertEquals(0.95, $row['spell_evasion']);
    }

    public function test_cache_overwrites_existing_entries()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        Cache::put('monsters', ['sentinel' => true]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $this->service->buildCache();

        $monsters = Cache::get('monsters');

        $this->assertArrayNotHasKey('sentinel', $monsters);
    }

    public function test_special_location_percent_increase_applies_to_stats()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'drop_chance_bonus' => 0.05,
            'only_during_event_type' => null,
        ]);

        $this->createLocation([
            'name' => 'Lava Rift',
            'game_map_id' => $map->id,
            'type' => 77,
            'enemy_strength_increase' => 2.0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'only_for_location_type' => 77,
            'str' => 10,
        ]);

        $this->service->buildSpecialLocationMonsterList();

        $cache = Cache::get('special-location-monsters');
        $row = collect($cache['location-type-77']['data'])->firstWhere('id', $monster->id);

        $this->assertSame(31, $row['str']);
    }

    public function test_special_location_drop_chance_uses_map_and_location_bonuses()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'drop_chance_bonus' => 0.05,
            'only_during_event_type' => null,
        ]);

        $this->createLocation([
            'name' => 'Lava Rift',
            'game_map_id' => $map->id,
            'type' => 77,
            'enemy_strength_increase' => 2.0,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'only_for_location_type' => 77,
            'drop_check' => 0.80,
        ]);

        $this->service->buildSpecialLocationMonsterList();

        $cache = Cache::get('special-location-monsters');
        $row = collect($cache['location-type-77']['data'])->firstWhere('id', $monster->id);

        $expected = 0.80 + 0.05 + LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(2.0);
        $expected = $expected > 1.0 ? 1.0 : $expected;

        $this->assertEquals($expected, $row['drop_chance']);
    }

    public function test_regular_map_does_not_cap_entrancing()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'entrancing_chance' => 0.90,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.90, $row['entrancing_chance']);
    }

    public function test_regular_map_does_not_cap_affix_resistance()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'affix_resistance' => 0.90,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.90, $row['affix_resistance']);
    }

    public function test_regular_map_does_not_cap_spell_evasion()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'spell_evasion' => 0.90,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.90, $row['spell_evasion']);
    }

    public function test_regular_map_does_not_cap_devouring_light()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'devouring_light_chance' => 0.74,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.74, $row['devouring_light_chance']);
    }

    public function test_regular_map_does_not_cap_devouring_darkness()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'devouring_darkness_chance' => 0.74,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.74, $row['devouring_darkness_chance']);
    }

    public function test_regular_map_does_not_cap_accuracy()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'accuracy' => 0.95,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.95, $row['accuracy']);
    }

    public function test_regular_map_does_not_cap_casting_accuracy()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'casting_accuracy' => 0.95,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.95, $row['casting_accuracy']);
    }

    public function test_regular_map_does_not_cap_dodge()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'dodge' => 0.95,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.95, $row['dodge']);
    }

    public function test_caps_drop_chance_from_drop_check_at_0_99()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'drop_check' => 0.995,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.99, $row['drop_chance']);
    }

    public function test_regular_map_does_not_apply_map_drop_bonus()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'drop_chance_bonus' => 0.05,
            'only_during_event_type' => null,
        ]);

        $monster = $this->createMonster([
            'game_map_id' => $map->id,
            'drop_check' => 0.80,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $monster->id);
        $this->assertEquals(0.80, $row['drop_chance']);
    }
}
