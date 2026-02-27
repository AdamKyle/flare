<?php

namespace Tests\Console\Events;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Registry\EventEnderRegistry;
use App\Game\Events\Services\ScheduleEventFinalizerService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateScheduledEvent;

class EndScheduledEventTest extends TestCase
{
    use CreateEvent, CreateScheduledEvent, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_returns_early_when_no_scheduled_events(): void
    {
        $registry = Mockery::mock(EventEnderRegistry::class);
        $registry->shouldNotReceive('end');
        $this->instance(EventEnderRegistry::class, $registry);

        $finalizer = Mockery::mock(ScheduleEventFinalizerService::class);
        $finalizer->shouldNotReceive('markNotRunningAndBroadcast');
        $this->instance(ScheduleEventFinalizerService::class, $finalizer);

        $this->artisan('end:scheduled-event');
        $this->assertEquals(0, ScheduledEvent::count());
    }

    public function test_finalizes_when_no_current_event(): void
    {
<<<<<<< HEAD
        $scheduled = $this->createScheduledEvent([
=======
        $this->deleteOtherGameMaps();

        $monster = $this->createMonster();
        $item = $this->createItem();

        $gameMap = $this->createGameMap();

        $location = $this->createLocation();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'raid_monster_ids' => [$monster->id],
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [$location->id],
            'artifact_item_id' => $item->id,
        ]);

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'start_date' => now()->addMinutes(5),
            'raid_id' => $raid,
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::RAID_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinutes(10),
            'raid_id' => $raid->id,
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        (new CharacterFactory)->createBaseCharacter()->givePlayerLocation(
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

    public function testEndWeeklyCurrencyEvent()
    {
        $this->deleteOtherGameMaps();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::WEEKLY_CURRENCY_DROPS,
            'started_at' => now(),
            'ends_at' => now()->subMinute(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertFalse($scheduledEvent->refresh()->currently_running);
    }

    public function testEndWeeklyCelestialEvent()
    {
        $this->deleteOtherGameMaps();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->subMinutes(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertFalse($scheduledEvent->refresh()->currently_running);
    }

    public function testEndWeeklyFactionLoyaltyEvent()
    {
        $this->deleteOtherGameMaps();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinutes(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertFalse($scheduledEvent->refresh()->currently_running);
    }

    public function testEndFeedbackEventWithSubmittedSurveys()
    {
        $this->deleteOtherGameMaps();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::FEEDBACK_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinutes(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $survey = $this->createSurvey();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $this->createSubmittedSurvey([
            'character_id' => $character->id,
            'survey_id' => $survey->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
        $this->assertFalse($scheduledEvent->refresh()->currently_running);

        $surveyResponse = SurveySnapshot::first();

        $this->assertNotNull($surveyResponse);
    }

    public function testEndsEventsRunningWhenNoScheduledEventsAreRunning()
    {
        $this->deleteOtherGameMaps();

        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->subMinutes(10),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->subMinutes(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(0, Event::count());
        $this->assertEquals(0, Announcement::count());
    }

    public function testEndWinterEvent()
    {
        $this->deleteOtherGameMaps();

        $monsterCache = [
            MapNameValue::SURFACE => [$this->createMonster()],
        ];

        Cache::put('monsters', $monsterCache);

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
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
>>>>>>> master
            'event_type' => EventType::WINTER_EVENT,
            'end_date' => now()->subMinute(),
            'currently_running' => true,
        ]);

        $registry = Mockery::mock(EventEnderRegistry::class);
        $registry->shouldNotReceive('end');
        $this->instance(EventEnderRegistry::class, $registry);

        $finalizer = Mockery::mock(ScheduleEventFinalizerService::class, function (MockInterface $m) use ($scheduled) {
            $m->shouldReceive('markNotRunningAndBroadcast')->once()->with(Mockery::on(function ($arg) use ($scheduled) {
                return $arg instanceof ScheduledEvent && $arg->id === $scheduled->id;
            }));
        });
        $this->instance(ScheduleEventFinalizerService::class, $finalizer);

        $this->artisan('end:scheduled-event');

        $this->assertEquals(1, ScheduledEvent::count());
        $this->assertEquals($scheduled->id, ScheduledEvent::first()->id);
    }

    public function test_ends_via_registry_then_finalizes(): void
    {
<<<<<<< HEAD
        $scheduled = $this->createScheduledEvent([
=======
        $this->deleteOtherGameMaps();

        $monsterCache = [
            MapNameValue::SURFACE => [$this->createMonster()],
        ];

        Cache::put('monsters', $monsterCache);

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
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
>>>>>>> master
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'end_date' => now()->subMinute(),
            'currently_running' => true,
        ]);

        $current = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'started_at' => now()->subHours(2),
            'ends_at' => now()->subMinute(),
        ]);

        $registry = Mockery::mock(EventEnderRegistry::class, function (MockInterface $m) use ($scheduled, $current) {
            $m->shouldReceive('end')->once()->with(
                Mockery::on(function ($type) {
                    return $type instanceof EventType;
                }),
                Mockery::on(function ($sched) use ($scheduled) {
                    return $sched instanceof ScheduledEvent && $sched->id === $scheduled->id;
                }),
                Mockery::on(function ($evt) use ($current) {
                    return $evt instanceof Event && $evt->id === $current->id;
                })
            );
        });
        $this->instance(EventEnderRegistry::class, $registry);

        $finalizer = Mockery::mock(ScheduleEventFinalizerService::class, function (MockInterface $m) use ($scheduled) {
            $m->shouldReceive('markNotRunningAndBroadcast')->once()->with(Mockery::on(function ($arg) use ($scheduled) {
                return $arg instanceof ScheduledEvent && $arg->id === $scheduled->id;
            }));
        });
        $this->instance(ScheduleEventFinalizerService::class, $finalizer);

        $this->artisan('end:scheduled-event');

<<<<<<< HEAD
        $this->assertEquals($scheduled->id, ScheduledEvent::first()->id);
        $this->assertEquals($current->id, Event::first()->id);
=======
        $character = $character->refresh();

        $this->assertNotEmpty($character->inventory->slots->where('item.specialty_type', ItemSpecialtyType::DELUSIONAL_SILVER)->all());
        $this->assertEmpty($character->kingdoms);
        $this->assertEquals(GameMap::where('name', MapNameValue::SURFACE)->first()->id, $character->map->game_map_id);

        $this->assertFalse($scheduledEvent->refresh()->currently_running);
        $this->assertEmpty(Event::all());
        $this->assertEmpty(Announcement::all());
    }

    public function testEndWinterEventWhilePledgedToFactionAndHelpingNPc()
    {
        $this->deleteOtherGameMaps();

        // We go back to this map when the event ends.
        $this->createGameMap([
            'name' => MapNameValue::SURFACE,
        ]);

        $icePlane = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
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
            'game_map_id' => $character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $character->factions->where('game_map_id', $icePlane->id)->first()->id,
            'character_id' => $character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $monsterCache = [
            MapNameValue::SURFACE => [$this->createMonster()],
        ];

        Cache::put('monsters', $monsterCache);

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
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
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinute(10),
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

    public function testEndWinterEventWhileNFactionLoayltyExists()
    {
        $this->deleteOtherGameMaps();

        // We go back to this map when the event ends.
        $this->createGameMap([
            'name' => MapNameValue::SURFACE,
        ]);

        $icePlane = $this->createGameMap([
            'name' => MapNameValue::ICE_PLANE,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->assignFactionSystem()
            ->givePlayerLocation(16, 16, $icePlane)
            ->kingdomManagement()
            ->assignKingdom([
                'game_map_id' => $icePlane->id,
            ])
            ->assignBuilding()
            ->assignUnits()
            ->getCharacter();

        $monsterCache = [
            MapNameValue::SURFACE => [$this->createMonster()],
        ];

        Cache::put('monsters', $monsterCache);

        $this->instance(
            MapTileValue::class,
            Mockery::mock(MapTileValue::class, function (MockInterface $mock) {
                $mock->shouldReceive('setUp')->andReturnSelf();
                $mock->shouldReceive('canWalk')->andReturn(true);
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
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'started_at' => now(),
            'ends_at' => now()->subMinute(10),
        ]);

        $this->createAnnouncement([
            'event_id' => $event->id,
        ]);

        $this->createItem(['specialty_type' => ItemSpecialtyType::CORRUPTED_ICE, 'type' => WeaponTypes::HAMMER]);

        $character = $character->refresh();

        $this->artisan('end:scheduled-event');

        $character = $character->refresh();

        $this->assertNotEmpty($character->inventory->slots->where('item.specialty_type', ItemSpecialtyType::CORRUPTED_ICE)->all());
        $this->assertEmpty($character->kingdoms);
        $this->assertEquals(GameMap::where('name', MapNameValue::SURFACE)->first()->id, $character->map->game_map_id);

        $this->assertEmpty($character->factionLoyalties()->where('is_pledged', true)->get());

        $this->assertFalse($scheduledEvent->refresh()->currently_running);
        $this->assertEmpty(Event::all());
        $this->assertEmpty(Announcement::all());
    }

    protected function deleteOtherGameMaps(): void
    {
        GameMap::whereIn('name', MapNameValue::$values)->delete();
>>>>>>> master
    }
}
