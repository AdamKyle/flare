<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Event;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Services\DevelopmentEventManager;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Raids\Values\RaidType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class DevelopmentEventManagerTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    public function testValidatesEverySelectedOptionBeforeOpeningTransaction(): void
    {
        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest([DevelopmentEventManager::INDEPENDENT_RAID], [
            DevelopmentEventManager::INDEPENDENT_RAID => 'Does Not Exist',
        ]);
    }

    public function testCreatesNoRecordsWhenAnyRaidBearingSelectionCannotBeResolved(): void
    {
        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest(
            [DevelopmentEventManager::WEEKLY_CELESTIALS, DevelopmentEventManager::INDEPENDENT_RAID],
            [DevelopmentEventManager::INDEPENDENT_RAID => 'Does Not Exist']
        );
    }

    public function testCreatesOneWeeklyScheduleWithExactOneMinuteFiveMinuteWindow(): void
    {
        Queue::fake();

        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest([DevelopmentEventManager::WEEKLY_CELESTIALS], []);

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(1, $created);

        $schedule = $created[0]->fresh();

        $this->assertTrue($schedule->start_date->equalTo(Carbon::parse('2026-01-01 12:01:00')));
        $this->assertEquals(300, $schedule->start_date->diffInSeconds($schedule->end_date));

        Carbon::setTestNow();
    }

    public function testCreatesMultipleCompatibleSchedulesAtomically(): void
    {
        Queue::fake();

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest([
            DevelopmentEventManager::WEEKLY_CELESTIALS,
            DevelopmentEventManager::WEEKLY_CURRENCY_DROPS,
        ], []);

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(2, $created);
        $this->assertEquals(2, ScheduledEvent::count());
    }

    public function testCreatesSeasonalParentAndExactLinkedChildRaidAtomically(): void
    {
        Queue::fake();

        $manager = resolve(DevelopmentEventManager::class);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT],
            [DevelopmentEventManager::WINTER_EVENT => $raid->name]
        );

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(2, $created);

        $parent = ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->firstOrFail();
        $child = ScheduledEvent::where('event_type', EventType::RAID_EVENT)->firstOrFail();

        $this->assertEquals($parent->id, $child->parent_scheduled_event_id);
        $this->assertEquals($raid->id, $child->raid_id);
        $this->assertEquals(ScheduledEventStatus::SCHEDULED, $child->status);
        $this->assertTrue($parent->start_date->equalTo($child->start_date));
        $this->assertTrue($parent->end_date->equalTo($child->end_date));

        Queue::assertPushed(\App\Game\Events\Jobs\InitiateWinterEvent::class, function ($job) use ($parent) {
            return $job->delay instanceof \DateTimeInterface && $job->delay->getTimestamp() === $parent->start_date->getTimestamp();
        });
        Queue::assertPushed(\App\Game\Events\Jobs\InitiateWinterEvent::class, 1);
        Queue::assertNotPushed(\App\Game\Raids\Jobs\InitiateRaid::class);
    }

    public function testRejectsDuplicateActiveNonRaidType(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest([DevelopmentEventManager::WEEKLY_CELESTIALS], []);
    }

    public function testRejectsDuplicateActiveExactRaid(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest(
            [DevelopmentEventManager::INDEPENDENT_RAID],
            [DevelopmentEventManager::INDEPENDENT_RAID => $raid->name]
        );
    }

    public function testRejectsRequestedSameMapRaidsBeforeWrites(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $raidOne = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $raidTwo = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT, DevelopmentEventManager::DELUSIONAL_EVENT],
            [
                DevelopmentEventManager::WINTER_EVENT => $raidOne->name,
                DevelopmentEventManager::DELUSIONAL_EVENT => $raidTwo->name,
            ]
        );
    }

    public function testPermitsRequestedDifferentMapRaids(): void
    {
        Queue::fake();

        $gameMapOne = $this->createGameMap(['name' => 'Surface']);
        $gameMapTwo = $this->createGameMap(['name' => 'Labyrinth']);
        $locationOne = $this->createLocation(['game_map_id' => $gameMapOne->id]);
        $locationTwo = $this->createLocation(['game_map_id' => $gameMapTwo->id]);

        $raidOne = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $locationOne->id,
            'corrupted_location_ids' => [],
        ]);

        $raidTwo = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $locationTwo->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT, DevelopmentEventManager::DELUSIONAL_EVENT],
            [
                DevelopmentEventManager::WINTER_EVENT => $raidOne->name,
                DevelopmentEventManager::DELUSIONAL_EVENT => $raidTwo->name,
            ]
        );

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(4, $created);
        Queue::assertPushed(\App\Game\Events\Jobs\InitiateWinterEvent::class, 1);
        Queue::assertPushed(\App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent::class, 1);
        Queue::assertNotPushed(\App\Game\Raids\Jobs\InitiateRaid::class);
    }

    public function testDecliningExistingConflictChangesNothing(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $activeRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $activeSchedule = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $activeRaid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $requestedRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::INDEPENDENT_RAID],
            [DevelopmentEventManager::INDEPENDENT_RAID => $requestedRaid->name]
        );

        $conflicts = $manager->findActiveConflicts($resolved);

        $this->assertNotEmpty($conflicts);
        $this->assertEquals(1, ScheduledEvent::count());
        $this->assertEquals(ScheduledEventStatus::RUNNING, $activeSchedule->fresh()->status);
    }

    public function testAcceptingExistingConflictCompletesTeardownBeforeCreatingReplacement(): void
    {
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $activeRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $activeSchedule = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $activeRaid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $requestedRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::INDEPENDENT_RAID],
            [DevelopmentEventManager::INDEPENDENT_RAID => $requestedRaid->name]
        );

        $conflicts = $manager->findActiveConflicts($resolved);

        $manager->startAfterConfirmation($resolved, $conflicts);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $activeSchedule->fresh()->status);
        $this->assertFalse(
            ScheduledEvent::whereIn('status', ScheduledEventStatus::activeStatuses())
                ->where('id', $activeSchedule->id)
                ->exists()
        );

        $replacementSchedules = ScheduledEvent::where('raid_id', $requestedRaid->id)
            ->whereIn('status', ScheduledEventStatus::activeStatuses())
            ->get();

        $this->assertCount(1, $replacementSchedules);
        $this->assertEquals($requestedRaid->id, $replacementSchedules->first()->raid_id);
    }

    public function testDispatchHappensOnlyAfterRecordsCommit(): void
    {
        Queue::fake();

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest([DevelopmentEventManager::WEEKLY_CELESTIALS], []);

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertEquals(ScheduledEventStatus::QUEUED, $created[0]->fresh()->status);
        Queue::assertPushed(\App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent::class, 1);
    }

    public function testFailedCreateDoesNotLeaveActiveFalseSuccessRecord(): void
    {
        Queue::fake();

        $manager = resolve(DevelopmentEventManager::class);

        $malformedResolvedRequest = [[
            'label' => DevelopmentEventManager::INDEPENDENT_RAID,
            'event_type' => EventType::RAID_EVENT,
            'raid' => null,
        ]];

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Attempt to read property "id" on null');

        $manager->startAfterConfirmation($malformedResolvedRequest, []);
    }

    public function testActiveWinterRejectsAnotherWinter(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT],
            [DevelopmentEventManager::WINTER_EVENT => $raid->name]
        );
    }

    public function testActiveDelusionalRejectsAnotherDelusional(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest(
            [DevelopmentEventManager::DELUSIONAL_EVENT],
            [DevelopmentEventManager::DELUSIONAL_EVENT => $raid->name]
        );
    }

    public function testActiveWinterPermitsDelusional(): void
    {
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::DELUSIONAL_EVENT],
            [DevelopmentEventManager::DELUSIONAL_EVENT => $raid->name]
        );

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(2, $created);
        $this->assertEquals(1, ScheduledEvent::where('event_type', EventType::DELUSIONAL_MEMORIES_EVENT)->count());
    }

    public function testActiveDelusionalPermitsWinter(): void
    {
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT],
            [DevelopmentEventManager::WINTER_EVENT => $raid->name]
        );

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(2, $created);
        $this->assertEquals(1, ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->count());
    }

    public function testCancelledWinterDoesNotBlockNewWinter(): void
    {
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::CANCELLED,
            'currently_running' => false,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT],
            [DevelopmentEventManager::WINTER_EVENT => $raid->name]
        );

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(2, $created);
        $this->assertEquals(
            1,
            ScheduledEvent::where('event_type', EventType::WINTER_EVENT)
                ->whereIn('status', ScheduledEventStatus::activeStatuses())
                ->count()
        );
    }

    public function testCompletedWinterDoesNotBlockNewWinter(): void
    {
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::COMPLETED,
            'currently_running' => false,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT],
            [DevelopmentEventManager::WINTER_EVENT => $raid->name]
        );

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(2, $created);
        $this->assertEquals(
            1,
            ScheduledEvent::where('event_type', EventType::WINTER_EVENT)
                ->whereIn('status', ScheduledEventStatus::activeStatuses())
                ->count()
        );
    }

    public function testFailedWinterDoesNotBlockNewWinter(): void
    {
        Queue::fake();

        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::FAILED,
            'currently_running' => false,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT],
            [DevelopmentEventManager::WINTER_EVENT => $raid->name]
        );

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertCount(2, $created);
        $this->assertEquals(
            1,
            ScheduledEvent::where('event_type', EventType::WINTER_EVENT)
                ->whereIn('status', ScheduledEventStatus::activeStatuses())
                ->count()
        );
    }

    public function testRunningScheduleRowsReturnsWeeklyEventRunningNow(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $rows = $manager->runningScheduleRows();

        $this->assertCount(1, $rows);
        $this->assertEquals($scheduledEvent->id, $rows[0]->id);
    }

    public function testRunningScheduleRowsExcludesFutureScheduledWeeklyEvent(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertEmpty($manager->runningScheduleRows());
    }

    public function testRunningScheduleRowsExcludesQueuedDevelopmentEvent(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::QUEUED,
            'currently_running' => false,
            'start_date' => now()->addMinute(),
            'end_date' => now()->addMinutes(6),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertEmpty($manager->runningScheduleRows());
    }

    public function testRunningScheduleRowsExcludesStartingEvent(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::STARTING,
            'currently_running' => true,
            'start_date' => now()->subMinute(),
            'end_date' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertEmpty($manager->runningScheduleRows());
    }

    public function testRunningScheduleRowsExcludesCancellingEvent(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::CANCELLING,
            'currently_running' => true,
            'start_date' => now()->subMinute(),
            'end_date' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertEmpty($manager->runningScheduleRows());
    }

    public function testRunningScheduleRowsExcludesExpiredRowStillMarkedRunning(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(10),
            'end_date' => now()->subMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertEmpty($manager->runningScheduleRows());
    }

    public function testRunningScheduleRowsExcludesRunningStatusRowWithNoRuntimeEvent(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinute(),
            'end_date' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertEmpty($manager->runningScheduleRows());
    }

    public function testRunningScheduleRowsReturnsRunningSeasonalParent(): void
    {
        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $rows = $manager->runningScheduleRows();

        $this->assertCount(1, $rows);
        $this->assertEquals($parent->id, $rows[0]->id);
    }

    public function testRunningScheduleRowsReturnsExactRunningChildRaidImmediatelyAfterParent(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $child = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $child->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $rows = $manager->runningScheduleRows();

        $this->assertCount(2, $rows);
        $this->assertEquals($parent->id, $rows[0]->id);
        $this->assertEquals($child->id, $rows[1]->id);
    }

    public function testRunningScheduleRowsExcludesFutureChildRaidUnderRunningSeasonalParent(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $rows = $manager->runningScheduleRows();

        $this->assertCount(1, $rows);
        $this->assertEquals($parent->id, $rows[0]->id);
    }

    public function testRunningScheduleRowsExcludesPastChildRaidUnderRunningSeasonalParent(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::COMPLETED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(20),
            'end_date' => now()->subMinutes(15),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $rows = $manager->runningScheduleRows();

        $this->assertCount(1, $rows);
        $this->assertEquals($parent->id, $rows[0]->id);
    }

    public function testRunningScheduleRowsDoesNotAttachRaidFromAnotherSeasonalScheduleOfSameType(): void
    {
        $gameMapOne = $this->createGameMap(['name' => 'Ice Plane One']);
        $gameMapTwo = $this->createGameMap(['name' => 'Ice Plane Two']);
        $locationOne = $this->createLocation(['game_map_id' => $gameMapOne->id]);
        $locationTwo = $this->createLocation(['game_map_id' => $gameMapTwo->id]);

        $raidOne = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $locationOne->id,
            'corrupted_location_ids' => [],
        ]);

        $raidTwo = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $locationTwo->id,
            'corrupted_location_ids' => [],
        ]);

        $parentOne = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parentOne->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $childOne = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidOne->id,
            'parent_scheduled_event_id' => $parentOne->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $childOne->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $parentTwo = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parentTwo->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $childTwo = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidTwo->id,
            'parent_scheduled_event_id' => $parentTwo->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $childTwo->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $rows = $manager->runningScheduleRows();

        $this->assertCount(4, $rows);
        $this->assertEquals($parentOne->id, $rows[0]->id);
        $this->assertEquals($childOne->id, $rows[1]->id);
        $this->assertEquals($parentTwo->id, $rows[2]->id);
        $this->assertEquals($childTwo->id, $rows[3]->id);
    }

    public function testCancelSpecificCancelsOnlyExactRowsStillRunningNow(): void
    {
        $runningSchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $runningSchedule->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $futureSchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CURRENCY_DROPS,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $manager->cancelSpecific([$runningSchedule, $futureSchedule]);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $runningSchedule->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::SCHEDULED, $futureSchedule->fresh()->status);
    }

    public function testCancelSpecificSelectingParentAndChildDoesNotCancelChildTwice(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $child = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $child->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $manager->cancelSpecific([$parent, $child]);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $parent->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::CANCELLED, $child->fresh()->status);
        $this->assertEquals(0, Event::count());
    }

    public function testCancelAllRunningIgnoresFutureScheduledRows(): void
    {
        $futureSchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $manager->cancelAllRunning();

        $this->assertEquals(ScheduledEventStatus::SCHEDULED, $futureSchedule->fresh()->status);
    }

    public function testCancelAllRunningIgnoresQueuedRows(): void
    {
        $queuedSchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::QUEUED,
            'currently_running' => false,
            'start_date' => now()->addMinute(),
            'end_date' => now()->addMinutes(6),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $manager->cancelAllRunning();

        $this->assertEquals(ScheduledEventStatus::QUEUED, $queuedSchedule->fresh()->status);
    }

    public function testCancelAllRunningIgnoresStaleExpiredRunningRows(): void
    {
        $expiredSchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(10),
            'end_date' => now()->subMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $expiredSchedule->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $manager->cancelAllRunning();

        $this->assertEquals(ScheduledEventStatus::RUNNING, $expiredSchedule->fresh()->status);
    }

    public function testCancelAllRunningUsesChildBeforeParentOrder(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);

        $gameMapOne = $this->createGameMap(['name' => 'Surface']);
        $gameMapTwo = $this->createGameMap(['name' => 'Labyrinth']);
        $locationOne = $this->createLocation(['game_map_id' => $gameMapOne->id]);
        $locationTwo = $this->createLocation(['game_map_id' => $gameMapTwo->id]);

        $independentRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $locationOne->id,
            'corrupted_location_ids' => [],
        ]);

        $childRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $locationTwo->id,
            'corrupted_location_ids' => [],
        ]);

        $weeklySchedule = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $weeklySchedule->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $independentSchedule = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $independentRaid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $independentSchedule->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $child = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $childRaid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $child->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $manager->cancelAllRunning();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $weeklySchedule->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::CANCELLED, $independentSchedule->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::CANCELLED, $parent->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::CANCELLED, $child->fresh()->status);
    }

    public function testRunningSeasonalParentExcludesFutureWinterSchedule(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertNull($manager->runningSeasonalParent(EventType::WINTER_EVENT));
    }

    public function testRunningSeasonalParentExcludesQueuedWinterSchedule(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::QUEUED,
            'currently_running' => false,
            'start_date' => now()->addMinute(),
            'end_date' => now()->addMinutes(6),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertNull($manager->runningSeasonalParent(EventType::WINTER_EVENT));
    }

    public function testRunningSeasonalParentExcludesCancellingWinterSchedule(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::CANCELLING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertNull($manager->runningSeasonalParent(EventType::WINTER_EVENT));
    }

    public function testRunningSeasonalParentExcludesHistoricalWinterSchedule(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::COMPLETED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(20),
            'end_date' => now()->subMinutes(15),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->assertNull($manager->runningSeasonalParent(EventType::WINTER_EVENT));
    }

    public function testValidateStartRequestStillRejectsDuplicateActiveSeasonalEventForSeasonalRaidFlow(): void
    {
        $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::QUEUED,
            'currently_running' => false,
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest(
            [DevelopmentEventManager::WINTER_EVENT],
            [DevelopmentEventManager::WINTER_EVENT => $raid->name]
        );
    }

    public function testFindActiveConflictsStillDetectsSameMapConflictForSeasonalRaidAttachment(): void
    {
        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);

        $activeRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $activeRaid->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $seasonalRaid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'raid_type' => RaidType::ICE_QUEEN,
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $resolvedRequest = $manager->validateStartRequest(
            [DevelopmentEventManager::INDEPENDENT_RAID],
            [DevelopmentEventManager::INDEPENDENT_RAID => $seasonalRaid->name]
        );

        $conflicts = $manager->findActiveConflicts($resolvedRequest);

        $this->assertNotEmpty($conflicts);
    }

    public function testStartAfterConfirmationWithExistingSeasonalParentLeavesNoScheduleOnFailure(): void
    {
        Queue::fake();

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $manager = resolve(DevelopmentEventManager::class);

        $malformedResolvedRequest = [[
            'label' => DevelopmentEventManager::INDEPENDENT_RAID,
            'event_type' => EventType::RAID_EVENT,
            'raid' => null,
        ]];

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Attempt to read property "id" on null');

        $manager->startAfterConfirmation($malformedResolvedRequest, [], $parent);
    }
}
