<?php

namespace Tests\Console\Events;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Announcement;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\WeaponTypes;
use App\Game\Events\Values\EventType;
use App\Game\Maps\Values\MapTileValue;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAnnouncement;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class EndScheduledEventTest extends TestCase {
    use RefreshDatabase,
        CreateScheduledEvent,
        CreateRaid,
        CreateMonster,
        CreateItem,
        CreateLocation,
        CreateGameMap,
        CreateEvent,
        CreateAnnouncement,
        CreateNpc,
        CreateFactionLoyalty;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testEndRaidEvent() {
        $this->deleteOtherGameMaps();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $gameMap = $this->createGameMap();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id'                   => $monster->id,
            'raid_monster_ids'               => [$monster->id],
            'raid_boss_location_id'          => $location->id,
            'corrupted_location_ids'         => [$location->id],
            'artifact_item_id'               => $item->id,
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type'        => EventType::RAID_EVENT,
            'start_date'        => now()->addMinutes(5),
            'raid_id'           => $raid,
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type'        => EventType::RAID_EVENT,
            'started_at'  => now(),
            'ends_at'     => now()->subMinutes(10),
            'raid_id'     => $raid->id,
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        (new CharacterFactory())->createBaseCharacter()->givePlayerLocation(
            $location->x,
            $location->y
        );

        Cache::put('monsters', [
            $gameMap->name => [],
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertFalse($scheduledEvent->refresh()->currently_running);
    }

    public function testEndWeeklyCurrencyEvent() {
        $this->deleteOtherGameMaps();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type'        => EventType::WEEKLY_CURRENCY_DROPS,
            'start_date'        => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type'        => EventType::WEEKLY_CURRENCY_DROPS,
            'started_at'  => now(),
            'ends_at'     => now()->subMinute(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertFalse($scheduledEvent->refresh()->currently_running);
    }

    public function testEndWeeklyCelestialEvent() {
        $this->deleteOtherGameMaps();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type'        => EventType::WEEKLY_CELESTIALS,
            'start_date'        => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type'        => EventType::WEEKLY_CELESTIALS,
            'started_at'  => now(),
            'ends_at'     => now()->subMinutes(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertFalse($scheduledEvent->refresh()->currently_running);
    }

    public function testEndIsMonthlyPvpEvent() {
        $this->deleteOtherGameMaps();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type'        => EventType::MONTHLY_PVP,
            'start_date'        => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type'        => EventType::MONTHLY_PVP,
            'started_at'  => now(),
            'ends_at'     => now()->subMinutes(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertFalse($scheduledEvent->refresh()->currently_running);
    }

    public function testEndsEventsRunningWhenNoScheduledEventsAreRunning() {
        $this->deleteOtherGameMaps();

        $this->createScheduledEvent([
            'event_type'        => EventType::WEEKLY_CELESTIALS,
            'start_date'        => now()->addMinutes(5),
            'currently_running' => false,
        ]);

        $event = $this->createEvent([
            'type'        => EventType::WEEKLY_CELESTIALS,
            'started_at'  => now(),
            'ends_at'     => now()->subMinutes(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
    }

    public function testEndPVPMonthlyEvent() {
        $this->deleteOtherGameMaps();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type'        => EventType::MONTHLY_PVP,
            'start_date'        => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertFalse($scheduledEvent->refresh()->currently_running);
    }

    public function testEndWinterEvent() {
        $this->deleteOtherGameMaps();

        $monsterCache = [
            MapNameValue::SURFACE => [$this->createMonster()]
        ];

        Cache::put('monsters', $monsterCache);

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnDeathWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnMagma')->andReturn(true);
                $mock->shouldReceive('isPurgatoryWater')->andReturn(false);
                $mock->shouldReceive('isTwistedMemoriesWater')->andReturn(false);
                $mock->shouldReceive('isDelusionalMemoriesWater')->andReturn(false);
                $mock->shouldReceive('getTileColor')->andReturn('000');
            })
        );

        $scheduledEvent = $this->createScheduledEvent([
            'event_type'        => EventType::WINTER_EVENT,
            'start_date'        => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type'        => EventType::WINTER_EVENT,
            'started_at'  => now(),
            'ends_at'     => now()->subMinute(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $icePlane = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $icePlane)
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        $this->createItem(['specialty_type' => ItemSpecialtyType::CORRUPTED_ICE, 'type' => WeaponTypes::HAMMER]);

        $this->artisan('end:scheduled-event');

        $character = $character->refresh();

        $this->assertNotEmpty($character->inventory->slots->where('item.specialty_type', ItemSpecialtyType::CORRUPTED_ICE)->all());
        $this->assertEmpty($character->kingdoms);
        $this->assertEquals(GameMap::where('name', MapNameValue::SURFACE)->first()->id, $character->map->game_map_id);

        $this->assertFalse($scheduledEvent->refresh()->currently_running);
        $this->assertEmpty(Event::all());
        $this->assertEmpty(Announcement::all());
    }

    public function testEndDelusionalMemoriesEvent() {
        $this->deleteOtherGameMaps();

        $monsterCache = [
            MapNameValue::SURFACE => [$this->createMonster()]
        ];

        Cache::put('monsters', $monsterCache);

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnDeathWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnMagma')->andReturn(true);
                $mock->shouldReceive('isPurgatoryWater')->andReturn(false);
                $mock->shouldReceive('isTwistedMemoriesWater')->andReturn(false);
                $mock->shouldReceive('isDelusionalMemoriesWater')->andReturn(false);
                $mock->shouldReceive('getTileColor')->andReturn('000');
            })
        );

        $scheduledEvent = $this->createScheduledEvent([
            'event_type'        => EventType::DELUSIONAL_MEMORIES_EVENT,
            'start_date'        => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type'        => EventType::DELUSIONAL_MEMORIES_EVENT,
            'started_at'  => now(),
            'ends_at'     => now()->subMinute(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $delusionalMap = $this->createGameMap([
            'name' => MapNameValue::DELUSIONAL_MEMORIES,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation(16, 16, $delusionalMap)
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        $this->createItem(['specialty_type' => ItemSpecialtyType::DELUSIONAL_SILVER, 'type' => WeaponTypes::HAMMER]);

        $this->artisan('end:scheduled-event');

        $character = $character->refresh();

        $this->assertNotEmpty($character->inventory->slots->where('item.specialty_type', ItemSpecialtyType::DELUSIONAL_SILVER)->all());
        $this->assertEmpty($character->kingdoms);
        $this->assertEquals(GameMap::where('name', MapNameValue::SURFACE)->first()->id, $character->map->game_map_id);

        $this->assertFalse($scheduledEvent->refresh()->currently_running);
        $this->assertEmpty(Event::all());
        $this->assertEmpty(Announcement::all());
    }

    public function testEndWinterEventWhilePledgedToFactionAndHelpingNPc() {
        $this->deleteOtherGameMaps();

        // We go back to this map when the event ends.
        $this->createGameMap([
            'name' => MapNameValue::SURFACE,
        ]);

        $icePlane = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()
            ->assignFactionSystem()
            ->givePlayerLocation(16, 16, $icePlane)
            ->kingdomManagement()
            ->assignKingdom([
                'game_map_id' => $icePlane->id,
            ])
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id'   => $character->factions->where('game_map_id', $icePlane->id)->first()->id,
            'character_id' => $character->id,
            'is_pledged'   => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id'          => $factionLoyalty->id,
            'npc_id'                      => $npc->id,
            'current_level'               => 0,
            'max_level'                   => 25,
            'next_level_fame'             => 100,
            'currently_helping'           => true,
            'kingdom_item_defence_bonus'  => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id'      => $factionLoyalty->id,
            'faction_loyalty_npc_id'  => $factionNpc->id,
            'fame_tasks'              => [],
        ]);

        $monsterCache = [
            MapNameValue::SURFACE => [$this->createMonster()]
        ];

        Cache::put('monsters', $monsterCache);

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('canWalkOnWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnDeathWater')->andReturn(true);
                $mock->shouldReceive('canWalkOnMagma')->andReturn(true);
                $mock->shouldReceive('isPurgatoryWater')->andReturn(false);
                $mock->shouldReceive('isTwistedMemoriesWater')->andReturn(false);
                $mock->shouldReceive('isDelusionalMemoriesWater')->andReturn(false);
                $mock->shouldReceive('getTileColor')->andReturn('000');
            })
        );

        $scheduledEvent = $this->createScheduledEvent([
            'event_type'        => EventType::WINTER_EVENT,
            'start_date'        => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type'        => EventType::WINTER_EVENT,
            'started_at'  => now(),
            'ends_at'     => now()->subMinute(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->createItem(['specialty_type' => ItemSpecialtyType::CORRUPTED_ICE, 'type' => WeaponTypes::HAMMER]);

        $character = $character->refresh();

        $this->artisan('end:scheduled-event');

        $character = $character->refresh();
        $factionNpc = $factionNpc->refresh();

        $this->assertNotEmpty($character->inventory->slots->where('item.specialty_type', ItemSpecialtyType::CORRUPTED_ICE)->all());
        $this->assertEmpty($character->kingdoms);
        $this->assertEquals(GameMap::where('name', MapNameValue::SURFACE)->first()->id, $character->map->game_map_id);

        $this->assertEmpty($character->factionLoyalties()->where('is_pledged', true)->get());
        $this->assertFalse($factionNpc->currently_helping);

        $this->assertFalse($scheduledEvent->refresh()->currently_running);
        $this->assertEmpty(Event::all());
        $this->assertEmpty(Announcement::all());

    }

    protected function deleteOtherGameMaps(): void {
        GameMap::whereIn('name', MapNameValue::$values)->delete();
    }
}
