<?php

namespace Tests\Unit\Flare\Services;

use App\Flare\Models\ScheduledEvent;
use App\Flare\Services\EventSchedulerService;
use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class EventSchedulerServiceTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateLocation, CreateMonster, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    private ?EventSchedulerService $eventSchedulerService = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->eventSchedulerService = resolve(EventSchedulerService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->eventSchedulerService = null;
    }

    public function testReversedRaidRescheduleDatesAreFixed(): void
    {
        $futureDate = now()->addMonths(3);

        $gameMap = $this->createGameMap(['name' => 'TestMap', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $location->id,
        ]);

        $existingEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'start_date' => $futureDate->copy()->addDay(),
            'end_date' => $futureDate->copy()->addMonth(),
            'currently_running' => false,
        ]);

        $this->eventSchedulerService->generateFutureRaid($existingEvent, $futureDate->copy());

        $newEvent = ScheduledEvent::where('raid_id', $raid->id)
            ->where('id', '!=', $existingEvent->id)
            ->first();

        $this->assertNotNull($newEvent);
        $this->assertTrue($newEvent->start_date->lt($newEvent->end_date));
    }

    public function testDifferentMapRaidsMayOverlap(): void
    {
        $mapOne = $this->createGameMap(['name' => 'MapOne', 'default' => false]);
        $mapTwo = $this->createGameMap(['name' => 'MapTwo', 'default' => false]);

        $locationA = $this->createLocation(['game_map_id' => $mapOne->id]);
        $locationB = $this->createLocation(['game_map_id' => $mapTwo->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raidA = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationA->id,
        ]);

        $raidB = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationB->id,
        ]);

        $futureDate = now()->addMonths(3);
        $existingEndDate = $futureDate->copy()->addMonth();

        $existingEventA = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidA->id,
            'start_date' => $futureDate->copy()->addDay(),
            'end_date' => $existingEndDate,
            'currently_running' => false,
        ]);

        $shiftedStart = $existingEndDate->copy()->addHour();

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidB->id,
            'start_date' => $shiftedStart->copy()->addDay(),
            'end_date' => $shiftedStart->copy()->addDay()->addMonth(),
            'currently_running' => false,
        ]);

        $this->eventSchedulerService->generateFutureRaid($existingEventA, $futureDate->copy());

        $newEventA = ScheduledEvent::where('raid_id', $raidA->id)
            ->where('id', '!=', $existingEventA->id)
            ->first();

        $this->assertNotNull($newEventA);
        $this->assertEquals(
            $shiftedStart->format('Y-m-d H:i:s'),
            $newEventA->start_date->format('Y-m-d H:i:s')
        );
    }

    public function testSameMapRaidsMayNotOverlap(): void
    {
        $mapOne = $this->createGameMap(['name' => 'MapOne', 'default' => false]);

        $locationA = $this->createLocation(['game_map_id' => $mapOne->id]);
        $locationB = $this->createLocation(['game_map_id' => $mapOne->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raidA = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationA->id,
        ]);

        $raidB = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationB->id,
        ]);

        $futureDate = now()->addMonths(3);
        $existingEndDate = $futureDate->copy()->addMonth();

        $existingEventA = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidA->id,
            'start_date' => $futureDate->copy()->addDay(),
            'end_date' => $existingEndDate,
            'currently_running' => false,
        ]);

        $shiftedStart = $existingEndDate->copy()->addHour();

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidB->id,
            'start_date' => $shiftedStart->copy()->addDay(),
            'end_date' => $shiftedStart->copy()->addDay()->addMonth(),
            'currently_running' => false,
        ]);

        $this->eventSchedulerService->generateFutureRaid($existingEventA, $futureDate->copy());

        $newEventA = ScheduledEvent::where('raid_id', $raidA->id)
            ->where('id', '!=', $existingEventA->id)
            ->first();

        $this->assertNotNull($newEventA);
        $this->assertTrue(
            $newEventA->start_date->greaterThanOrEqualTo($shiftedStart->copy()->addMonth())
        );
    }

    public function testFutureSameRaidCannotOverlapItself(): void
    {
        $mapOne = $this->createGameMap(['name' => 'MapOne', 'default' => false]);
        $locationA = $this->createLocation(['game_map_id' => $mapOne->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raidA = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationA->id,
        ]);

        $futureDate = now()->addMonths(3);
        $existingEndDate = $futureDate->copy()->addMonth();

        $existingEventA = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidA->id,
            'start_date' => $futureDate->copy()->addDay(),
            'end_date' => $existingEndDate,
            'currently_running' => false,
        ]);

        $shiftedStart = $existingEndDate->copy()->addHour();

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidA->id,
            'start_date' => $shiftedStart->copy()->addDay(),
            'end_date' => $shiftedStart->copy()->addDay()->addMonth(),
            'currently_running' => false,
        ]);

        $this->eventSchedulerService->generateFutureRaid($existingEventA, $futureDate->copy());

        $newEventA = ScheduledEvent::where('raid_id', $raidA->id)
            ->where('id', '!=', $existingEventA->id)
            ->orderBy('start_date', 'desc')
            ->first();

        $this->assertNotNull($newEventA);
        $this->assertTrue(
            $newEventA->start_date->greaterThanOrEqualTo($shiftedStart->copy()->addMonth())
        );
    }

    public function testChildRaidOutsideParentWindowNotCreated(): void
    {
        $parentStart = now()->addMonth();
        $parentEnd = $parentStart->copy()->addMonths(2);

        $gameMap = $this->createGameMap(['name' => 'TestMap2', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $location->id,
            'scheduled_event_description' => 'test',
        ]);

        $parentEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $parentStart,
            'end_date' => $parentEnd,
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $parentStart->copy()->subDay()->format('Y-m-d H:i:s'),
                'end_date' => $parentStart->copy()->addDays(5)->format('Y-m-d H:i:s'),
            ]],
        ]);

        $this->eventSchedulerService->createRaidEventsForScheduledEventWith($parentEvent);

        $this->assertEquals(0, ScheduledEvent::where('raid_id', $raid->id)->count());
    }

    public function testSameMapChildRaidOverlapIsPrevented(): void
    {
        $parentStart = now()->addMonth();
        $parentEnd = $parentStart->copy()->addMonths(2);

        $gameMap = $this->createGameMap(['name' => 'SharedMap', 'default' => false]);
        $locationA = $this->createLocation(['game_map_id' => $gameMap->id]);
        $locationB = $this->createLocation(['game_map_id' => $gameMap->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raidA = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationA->id,
            'scheduled_event_description' => 'test',
        ]);

        $raidB = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationB->id,
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidB->id,
            'start_date' => $parentStart->copy()->addDays(3),
            'end_date' => $parentStart->copy()->addDays(8),
            'currently_running' => false,
        ]);

        $parentEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $parentStart,
            'end_date' => $parentEnd,
            'raids_for_event' => [[
                'selected_raid' => $raidA->id,
                'start_date' => $parentStart->copy()->addDays(4)->format('Y-m-d H:i:s'),
                'end_date' => $parentStart->copy()->addDays(7)->format('Y-m-d H:i:s'),
            ]],
        ]);

        $this->eventSchedulerService->createRaidEventsForScheduledEventWith($parentEvent);

        $this->assertEquals(0, ScheduledEvent::where('raid_id', $raidA->id)->count());
    }

    public function testDifferentMapChildRaidOverlapIsAllowed(): void
    {
        $parentStart = now()->addMonth();
        $parentEnd = $parentStart->copy()->addMonths(2);

        $gameMapA = $this->createGameMap(['name' => 'MapAlpha', 'default' => false]);
        $gameMapB = $this->createGameMap(['name' => 'MapBeta', 'default' => false]);
        $locationA = $this->createLocation(['game_map_id' => $gameMapA->id]);
        $locationB = $this->createLocation(['game_map_id' => $gameMapB->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raidA = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationA->id,
            'scheduled_event_description' => 'test',
        ]);

        $raidB = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationB->id,
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidB->id,
            'start_date' => $parentStart->copy()->addDays(3),
            'end_date' => $parentStart->copy()->addDays(8),
            'currently_running' => false,
        ]);

        $parentEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $parentStart,
            'end_date' => $parentEnd,
            'raids_for_event' => [[
                'selected_raid' => $raidA->id,
                'start_date' => $parentStart->copy()->addDays(4)->format('Y-m-d H:i:s'),
                'end_date' => $parentStart->copy()->addDays(7)->format('Y-m-d H:i:s'),
            ]],
        ]);

        $this->eventSchedulerService->createRaidEventsForScheduledEventWith($parentEvent);

        $this->assertEquals(1, ScheduledEvent::where('raid_id', $raidA->id)->count());
    }

    public function testSyncDoesNotDeleteUnrelatedManualRaidSchedules(): void
    {
        $gameMap = $this->createGameMap(['name' => 'TestMap3', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $monster = $this->createMonster();
        $item = $this->createItem();
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $location->id,
            'scheduled_event_description' => 'test',
        ]);

        $parentStart = now()->addMonth();
        $parentEnd = $parentStart->copy()->addMonths(2);

        $manualRaidEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'start_date' => $parentEnd->copy()->addMonths(2),
            'end_date' => $parentEnd->copy()->addMonths(3),
            'currently_running' => false,
        ]);

        $parentEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $parentStart,
            'end_date' => $parentEnd,
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $parentStart->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $parentStart->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ]);

        $this->eventSchedulerService->updateEvent([
            'selected_event_type' => EventType::WINTER_EVENT,
            'selected_raid' => null,
            'selected_start_date' => $parentStart,
            'selected_end_date' => $parentEnd,
            'event_description' => 'Updated',
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $parentStart->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $parentStart->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ], $parentEvent);

        $this->assertNotNull(ScheduledEvent::find($manualRaidEvent->id));
    }

    public function testUpdatingRaidsForEventPersistsButDoesNotCreateChildRaids(): void
    {
        $gameMap = $this->createGameMap(['name' => 'TestMap4', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $monster = $this->createMonster();
        $item = $this->createItem();
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $location->id,
            'scheduled_event_description' => 'test',
        ]);

        $parentStart = now()->addMonth();
        $parentEnd = $parentStart->copy()->addMonths(2);

        $parentEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $parentStart,
            'end_date' => $parentEnd,
            'raids_for_event' => null,
        ]);

        $childStart = $parentStart->copy()->addDays(5)->format('Y-m-d H:i:s');
        $childEnd = $parentStart->copy()->addDays(10)->format('Y-m-d H:i:s');

        $this->eventSchedulerService->updateEvent([
            'selected_event_type' => EventType::WINTER_EVENT,
            'selected_raid' => null,
            'selected_start_date' => $parentStart,
            'selected_end_date' => $parentEnd,
            'event_description' => 'Updated',
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $childStart,
                'end_date' => $childEnd,
            ]],
        ], $parentEvent);

        $this->assertNull(ScheduledEvent::where('raid_id', $raid->id)->first());

        $refreshed = $parentEvent->fresh();
        $this->assertNotNull($refreshed->raids_for_event);
        $this->assertEquals($raid->id, $refreshed->raids_for_event[0]['selected_raid']);
    }

    public function testUpdateEventDoesNotDeleteManualRaidInsideParentWindow(): void
    {
        $gameMap = $this->createGameMap(['name' => 'TestMapInner', 'default' => false]);
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $monster = $this->createMonster();
        $item = $this->createItem();
        $raid = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $location->id,
            'scheduled_event_description' => 'test',
        ]);

        $parentStart = now()->addMonth();
        $parentEnd = $parentStart->copy()->addMonths(2);

        $manualRaidEvent = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'start_date' => $parentStart->copy()->addDays(15),
            'end_date' => $parentStart->copy()->addDays(20),
            'currently_running' => false,
        ]);

        $parentEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $parentStart,
            'end_date' => $parentEnd,
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $parentStart->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $parentStart->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ]);

        $this->eventSchedulerService->updateEvent([
            'selected_event_type' => EventType::WINTER_EVENT,
            'selected_raid' => null,
            'selected_start_date' => $parentStart,
            'selected_end_date' => $parentEnd,
            'event_description' => 'Updated',
            'raids_for_event' => [[
                'selected_raid' => $raid->id,
                'start_date' => $parentStart->copy()->addDays(5)->format('Y-m-d H:i:s'),
                'end_date' => $parentStart->copy()->addDays(10)->format('Y-m-d H:i:s'),
            ]],
        ], $parentEvent);

        $this->assertNotNull(ScheduledEvent::find($manualRaidEvent->id));
    }

    public function testCreateRaidEventsForScheduledEventWithCreatesAllCurrentChildRaids(): void
    {
        $parentStart = now()->addMonth();
        $parentEnd = $parentStart->copy()->addMonths(2);

        $mapA = $this->createGameMap(['name' => 'MapForRaidA', 'default' => false]);
        $mapB = $this->createGameMap(['name' => 'MapForRaidB', 'default' => false]);
        $locationA = $this->createLocation(['game_map_id' => $mapA->id]);
        $locationB = $this->createLocation(['game_map_id' => $mapB->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raidA = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationA->id,
            'scheduled_event_description' => 'test',
        ]);

        $raidB = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationB->id,
            'scheduled_event_description' => 'test',
        ]);

        $parentEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $parentStart,
            'end_date' => $parentEnd,
            'raids_for_event' => [
                [
                    'selected_raid' => $raidA->id,
                    'start_date' => $parentStart->copy()->addDays(4)->format('Y-m-d H:i:s'),
                    'end_date' => $parentStart->copy()->addDays(7)->format('Y-m-d H:i:s'),
                ],
                [
                    'selected_raid' => $raidB->id,
                    'start_date' => $parentStart->copy()->addDays(10)->format('Y-m-d H:i:s'),
                    'end_date' => $parentStart->copy()->addDays(13)->format('Y-m-d H:i:s'),
                ],
            ],
        ]);

        $this->eventSchedulerService->createRaidEventsForScheduledEventWith($parentEvent);

        $this->assertEquals(1, ScheduledEvent::where('raid_id', $raidA->id)->count());
        $this->assertEquals(1, ScheduledEvent::where('raid_id', $raidB->id)->count());
    }

    public function testSameMapOverlapIsBlockedWhenOtherRaidSharesMapThroughCorruptedLocations(): void
    {
        $parentStart = now()->addMonth();
        $parentEnd = $parentStart->copy()->addMonths(2);

        $sharedMap = $this->createGameMap(['name' => 'SharedConflictMap', 'default' => false]);
        $bossOnlyMap = $this->createGameMap(['name' => 'BossOnlyMap', 'default' => false]);

        $locationOnSharedMap = $this->createLocation(['game_map_id' => $sharedMap->id]);
        $bossLocationOnOtherMap = $this->createLocation(['game_map_id' => $bossOnlyMap->id]);

        $monster = $this->createMonster();
        $item = $this->createItem();

        $raidA = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $locationOnSharedMap->id,
            'scheduled_event_description' => 'test',
        ]);

        $raidB = $this->createRaid([
            'raid_boss_id' => $monster->id,
            'artifact_item_id' => $item->id,
            'raid_boss_location_id' => $bossLocationOnOtherMap->id,
            'corrupted_location_ids' => [$locationOnSharedMap->id],
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidB->id,
            'start_date' => $parentStart->copy()->addDays(3),
            'end_date' => $parentStart->copy()->addDays(8),
            'currently_running' => false,
        ]);

        $parentEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'start_date' => $parentStart,
            'end_date' => $parentEnd,
            'raids_for_event' => [[
                'selected_raid' => $raidA->id,
                'start_date' => $parentStart->copy()->addDays(4)->format('Y-m-d H:i:s'),
                'end_date' => $parentStart->copy()->addDays(7)->format('Y-m-d H:i:s'),
            ]],
        ]);

        $this->eventSchedulerService->createRaidEventsForScheduledEventWith($parentEvent);

        $this->assertEquals(0, ScheduledEvent::where('raid_id', $raidA->id)->count());
    }
}
