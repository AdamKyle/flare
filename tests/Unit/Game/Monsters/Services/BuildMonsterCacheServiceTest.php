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

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = resolve(BuildMonsterCacheService::class);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        $this->service = null;

        parent::tearDown();
    }

    public function test_build_cache_for_regular_map()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $m1 = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'str' => 10,
            'dex' => 10,
            'agi' => 10,
            'dur' => 10,
            'chr' => 10,
            'int' => 10,
            'ac' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-3',
            'spell_evasion' => 0.10,
            'affix_resistance' => 0.10,
            'healing_percentage' => 0.10,
            'entrancing_chance' => 0.10,
            'devouring_light_chance' => 0.10,
            'devouring_darkness_chance' => 0.10,
            'accuracy' => 0.10,
            'casting_accuracy' => 0.10,
            'dodge' => 0.10,
            'criticality' => 0.10,
        ]);

        $m2 = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'str' => 12,
            'dex' => 12,
            'agi' => 12,
            'dur' => 12,
            'chr' => 12,
            'int' => 12,
            'ac' => 12,
            'health_range' => '12-24',
            'attack_range' => '2-4',
            'spell_evasion' => 0.20,
            'affix_resistance' => 0.20,
            'healing_percentage' => 0.20,
            'entrancing_chance' => 0.20,
            'devouring_light_chance' => 0.20,
            'devouring_darkness_chance' => 0.20,
            'accuracy' => 0.20,
            'casting_accuracy' => 0.20,
            'dodge' => 0.20,
            'criticality' => 0.20,
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');

        $this->assertIsArray($cache);
        $this->assertArrayHasKey($surface->name, $cache);
        $this->assertArrayHasKey('data', $cache[$surface->name]);

        $data = $cache[$surface->name]['data'];

        $this->assertCount(2, $data);

        $first = collect($data)->firstWhere('id', $m1->id);

        $this->assertNotNull($first);
        $this->assertEquals($surface->name, $first['map_name']);
        $this->assertTrue($first['is_special']);
        $this->assertSame(10, $first['str']);
        $this->assertSame('10-20', $first['health_range']);
        $this->assertSame('1-3', $first['attack_range']);
    }

    public function test_build_cache_for_event_map_splits_regular_and_easier()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $eventMap = $this->createGameMap([
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
            'game_map_id' => $eventMap->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');

        $this->assertArrayHasKey($eventMap->name, $cache);

        $eventEntry = $cache[$eventMap->name];

        $this->assertArrayHasKey('regular', $eventEntry);
        $this->assertArrayHasKey('easier', $eventEntry);
        $this->assertArrayHasKey('data', $eventEntry['regular']);
        $this->assertArrayHasKey('data', $eventEntry['easier']);

        $regularIds = collect($eventEntry['regular']['data'])->pluck('id')->all();
        $easierIds = collect($eventEntry['easier']['data'])->pluck('id')->all();

        $this->assertContains($eventMonster->id, $regularIds);
        $this->assertNotContains($surfaceMonster->id, $regularIds);

        $this->assertContains($surfaceMonster->id, $easierIds);
        $this->assertNotContains($eventMonster->id, $easierIds);

        $this->assertTrue(collect($eventEntry['regular']['data'])->first()['is_special']);
        $this->assertTrue(collect($eventEntry['easier']['data'])->first()['is_special']);
    }

    public function test_build_raid_cache_merges_bosses_and_critters_and_adds_location_entries()
    {
        $mapA = $this->createGameMap([
            'name' => 'Forest',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $crit1 = $this->createMonster([
            'game_map_id' => $mapA->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => true,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $boss1 = $this->createMonster([
            'game_map_id' => $mapA->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => true,
            'only_for_location_type' => null,
        ]);

        $regular = $this->createMonster([
            'game_map_id' => $mapA->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $loc = $this->createLocation([
            'name' => 'Thicket',
            'game_map_id' => $mapA->id,
            'enemy_strength_increase' => 1,
            'type' => null,
        ]);

        $this->service->buildRaidCache();

        $cache = Cache::get('raid-monsters');

        $this->assertArrayHasKey($mapA->name, $cache);
        $this->assertArrayHasKey('data', $cache[$mapA->name]);

        $raidIds = collect($cache[$mapA->name]['data'])->pluck('id')->all();

        $this->assertContains($crit1->id, $raidIds);
        $this->assertContains($boss1->id, $raidIds);
        $this->assertNotContains($regular->id, $raidIds);

        $this->assertArrayHasKey($loc->name, $cache);
        $this->assertArrayHasKey('data', $cache[$loc->name]);

        $locationIds = collect($cache[$loc->name]['data'])->pluck('id')->all();

        $this->assertContains($regular->id, $locationIds);
        $this->assertNotContains($crit1->id, $locationIds);
        $this->assertNotContains($boss1->id, $locationIds);

        $this->assertFalse(collect($cache[$mapA->name]['data'])->first()['is_special']);
        $this->assertTrue(collect($cache[$loc->name]['data'])->first()['is_special']);
    }

    public function test_build_special_location_monster_list_handles_empty_and_non_empty_types()
    {
        $map = $this->createGameMap([
            'name' => 'Caves',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $locTypeA = $this->createLocation([
            'name' => 'Crystal Cavern',
            'game_map_id' => $map->id,
            'type' => 10,
        ]);

        $locTypeB = $this->createLocation([
            'name' => 'Empty Grotto',
            'game_map_id' => $map->id,
            'type' => 20,
        ]);

        $mA = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => 10,
        ]);

        $this->service->buildSpecialLocationMonsterList();

        $cache = Cache::get('special-location-monsters');

        $this->assertArrayHasKey('location-type-10', $cache);
        $this->assertArrayHasKey('data', $cache['location-type-10']);
        $ids = collect($cache['location-type-10']['data'])->pluck('id')->all();
        $this->assertContains($mA->id, $ids);

        $this->assertArrayHasKey('location-type-20', $cache);
        $this->assertArrayHasKey('data', $cache['location-type-20']);
        $this->assertCount(0, $cache['location-type-20']['data']);
    }

    public function test_build_celestial_cache_only_includes_celestials_without_location_type()
    {
        $map = $this->createGameMap([
            'name' => 'Sky',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $celOk = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => true,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $celFiltered = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => true,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => 99,
        ]);

        $nonCel = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $this->service->buildCelesetialCache();

        $cache = Cache::get('celestials');

        $this->assertArrayHasKey($map->name, $cache);
        $this->assertArrayHasKey('data', $cache[$map->name]);

        $ids = collect($cache[$map->name]['data'])->pluck('id')->all();

        $this->assertContains($celOk->id, $ids);
        $this->assertNotContains($celFiltered->id, $ids);
        $this->assertNotContains($nonCel->id, $ids);
    }

    public function test_manage_monsters_applies_flat_and_percentage_increases_with_caps()
    {
        $surface = $this->createGameMap([
            'name' => MapNameValue::SURFACE,
            'default' => true,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        $loc = $this->createLocation([
            'name' => 'Ruins',
            'game_map_id' => $surface->id,
            'enemy_strength_increase' => 1,
            'type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'str' => 10,
            'dex' => 10,
            'agi' => 10,
            'dur' => 10,
            'chr' => 10,
            'int' => 10,
            'ac' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-3',
            'spell_evasion' => 0.10,
            'affix_resistance' => 0.10,
            'healing_percentage' => 0.10,
            'entrancing_chance' => 0.10,
            'devouring_light_chance' => 0.10,
            'devouring_darkness_chance' => 0.10,
            'accuracy' => 0.10,
            'casting_accuracy' => 0.10,
            'dodge' => 0.10,
            'criticality' => 0.10,
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');

        $this->assertArrayHasKey($loc->name, $cache);
        $this->assertArrayHasKey('data', $cache[$loc->name]);

        $row = collect($cache[$loc->name]['data'])->firstWhere('id', $m->id);

        $this->assertNotNull($row);
        $this->assertSame(11, $row['str']);
        $this->assertSame(11, $row['dex']);
        $this->assertSame(11, $row['agi']);
        $this->assertSame(11, $row['dur']);
        $this->assertSame(11, $row['chr']);
        $this->assertSame(11, $row['int']);
        $this->assertSame(11, $row['ac']);
        $this->assertSame('11-21', $row['health_range']);
        $this->assertSame('2-4', $row['attack_range']);
        $this->assertEquals(0.95, $row['spell_evasion']);
        $this->assertEquals(0.95, $row['affix_resistance']);
        $this->assertEquals(1.0, $row['max_healing']);
        $this->assertEquals(0.95, $row['entrancing_chance']);
        $this->assertEquals(0.75, $row['devouring_light_chance']);
        $this->assertEquals(0.75, $row['devouring_darkness_chance']);
        $this->assertEquals(1.0, $row['accuracy']);
        $this->assertEquals(1.0, $row['casting_accuracy']);
        $this->assertEquals(1.0, $row['dodge']);
        $this->assertEquals(1.0, $row['criticality']);
        $this->assertTrue($row['is_special']);
    }

    public function test_special_maps_apply_percentage_and_caps()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
            'str' => 10,
            'spell_evasion' => 0.95,
        ]);

        $this->service->buildCache();

        $cache = Cache::get('monsters');

        $this->assertArrayHasKey($map->name, $cache);
        $this->assertArrayHasKey('data', $cache[$map->name]);

        $row = collect($cache[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertNotNull($row);
        $this->assertSame(11, $row['str']);
        $this->assertEquals(0.95, $row['spell_evasion']);
        $this->assertTrue($row['is_special']);
    }

    public function test_cache_delete_and_overwrite_behaviour()
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

        $this->assertIsArray($monsters);
        $this->assertArrayNotHasKey('sentinel', $monsters);

        $map = $this->createGameMap([
            'name' => 'Raids',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        Cache::put('raid-monsters', ['sentinel' => true]);

        $this->createMonster([
            'game_map_id' => $map->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => true,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $this->service->buildRaidCache();

        $raid = Cache::get('raid-monsters');

        $this->assertIsArray($raid);
        $this->assertArrayNotHasKey('sentinel', $raid);

        $celMap = $this->createGameMap([
            'name' => 'Sky Two',
            'default' => false,
            'enemy_stat_bonus' => 0.0,
            'only_during_event_type' => null,
        ]);

        Cache::put('celestials', ['sentinel' => true]);

        $this->createMonster([
            'game_map_id' => $celMap->id,
            'is_celestial_entity' => true,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $this->service->buildCelesetialCache();

        $cel = Cache::get('celestials');

        $this->assertIsArray($cel);
        $this->assertArrayNotHasKey('sentinel', $cel);

        Cache::put('special-location-monsters', ['sentinel' => true]);

        $this->createLocation([
            'name' => 'Hot Springs',
            'game_map_id' => $surface->id,
            'type' => 42,
        ]);

        $this->createMonster([
            'game_map_id' => $surface->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => 42,
        ]);

        $this->service->buildSpecialLocationMonsterList();

        $spec = Cache::get('special-location-monsters');

        $this->assertIsArray($spec);
        $this->assertArrayNotHasKey('sentinel', $spec);
        $this->assertArrayHasKey('location-type-42', $spec);
    }

    public function test_special_location_list_applies_map_and_location_stat_bonuses()
    {
        $hell = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'drop_chance_bonus' => 0.05,
            'only_during_event_type' => null,
        ]);

        $loc = $this->createLocation([
            'name' => 'Lava Rift',
            'game_map_id' => $hell->id,
            'type' => 77,
            'enemy_strength_increase' => 2.0,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $hell->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => 77,
            'str' => 10,
            'health_range' => '10-20',
            'attack_range' => '1-3',
            'drop_check' => 0.80,
        ]);

        $this->service->buildSpecialLocationMonsterList();

        $cache = Cache::get('special-location-monsters');

        $row = collect($cache['location-type-77']['data'])->firstWhere('id', $m->id);

        $locPct = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(2.0) / 100.0;
        $effectivePct = 0.10 + $locPct;

        $expectedStr = (int) round((10 + 2.0) + (10 + 2.0) * $effectivePct);
        $this->assertSame($expectedStr, $row['str']);
        $this->assertTrue($row['is_special']);
    }

    public function test_special_location_list_applies_drop_caps()
    {
        $hell = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'drop_chance_bonus' => 0.05,
            'only_during_event_type' => null,
        ]);

        $loc = $this->createLocation([
            'name' => 'Lava Rift',
            'game_map_id' => $hell->id,
            'type' => 77,
            'enemy_strength_increase' => 2.0,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $hell->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => 77,
            'drop_check' => 0.80,
        ]);

        $this->service->buildSpecialLocationMonsterList();

        $cache = Cache::get('special-location-monsters');

        $row = collect($cache['location-type-77']['data'])->firstWhere('id', $m->id);

        $locPct = LocationBasedEnemyDropChanceBonus::calculateDropChanceBonusPercent(2.0) / 100.0;
        $expectedDrop = 0.80 + 0.05 + $locPct;
        $expectedDrop = $expectedDrop > 1.0 ? 1.0 : $expectedDrop;

        $this->assertEquals($expectedDrop, $row['drop_chance']);
    }

    public function test_regular_map_caps_entrancing_to_95()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'entrancing_chance' => 0.90,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(0.95, $row['entrancing_chance']);
    }

    public function test_regular_map_caps_affix_resistance_to_95()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'affix_resistance' => 0.90,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(0.95, $row['affix_resistance']);
    }

    public function test_regular_map_caps_spell_evasion_to_95()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'spell_evasion' => 0.90,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(0.95, $row['spell_evasion']);
    }

    public function test_regular_map_caps_devouring_light_to_75()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'devouring_light_chance' => 0.74,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(0.75, $row['devouring_light_chance']);
    }

    public function test_regular_map_caps_devouring_darkness_to_75()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'devouring_darkness_chance' => 0.74,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(0.75, $row['devouring_darkness_chance']);
    }

    public function test_regular_map_caps_accuracy_to_100()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'accuracy' => 0.95,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(1.0, $row['accuracy']);
    }

    public function test_regular_map_caps_casting_accuracy_to_100()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'casting_accuracy' => 0.95,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(1.0, $row['casting_accuracy']);
    }

    public function test_regular_map_caps_dodge_to_100()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'dodge' => 0.95,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(1.0, $row['dodge']);
    }

    public function test_regular_map_caps_drop_chance_from_drop_check_to_99()
    {
        $map = $this->createGameMap([
            'name' => MapNameValue::HELL,
            'default' => false,
            'enemy_stat_bonus' => 0.10,
            'only_during_event_type' => null,
        ]);

        $m = $this->createMonster([
            'game_map_id' => $map->id,
            'drop_check' => 0.995,
        ]);

        $this->service->buildCache();

        $row = collect(Cache::get('monsters')[$map->name]['data'])->firstWhere('id', $m->id);

        $this->assertEquals(0.99, $row['drop_chance']);
    }
}
