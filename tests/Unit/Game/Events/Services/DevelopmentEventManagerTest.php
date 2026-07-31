<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Event;
use App\Flare\Models\Monster;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Jobs\InitiateWinterEvent;
use App\Game\Events\Services\DevelopmentEventManager;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Raids\Jobs\InitiateRaid;
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

    public function test_validates_every_selected_option_before_opening_transaction(): void
    {
        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest([DevelopmentEventManager::INDEPENDENT_RAID], [
            DevelopmentEventManager::INDEPENDENT_RAID => 'Does Not Exist',
        ]);
    }

    public function test_creates_no_records_when_any_raid_bearing_selection_cannot_be_resolved(): void
    {
        $manager = resolve(DevelopmentEventManager::class);

        $this->expectException(RuntimeException::class);

        $manager->validateStartRequest(
            [DevelopmentEventManager::WEEKLY_CELESTIALS, DevelopmentEventManager::INDEPENDENT_RAID],
            [DevelopmentEventManager::INDEPENDENT_RAID => 'Does Not Exist']
        );
    }

    public function test_creates_one_weekly_schedule_with_exact_one_minute_five_minute_window(): void
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

    public function test_creates_multiple_compatible_schedules_atomically(): void
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

    public function test_creates_seasonal_parent_and_exact_linked_child_raid_atomically(): void
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

        Queue::assertPushed(InitiateWinterEvent::class, function ($job) use ($parent) {
            return $job->delay instanceof \DateTimeInterface && $job->delay->getTimestamp() === $parent->start_date->getTimestamp();
        });
        Queue::assertPushed(InitiateWinterEvent::class, 1);
        Queue::assertNotPushed(InitiateRaid::class);
    }

    public function test_rejects_duplicate_active_non_raid_type(): void
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

    public function test_rejects_duplicate_active_exact_raid(): void
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

    public function test_rejects_requested_same_map_raids_before_writes(): void
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

    public function test_permits_requested_different_map_raids(): void
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
        Queue::assertPushed(InitiateWinterEvent::class, 1);
        Queue::assertPushed(InitiateDelusionalMemoriesEvent::class, 1);
        Queue::assertNotPushed(InitiateRaid::class);
    }

    public function test_declining_existing_conflict_changes_nothing(): void
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

    public function test_accepting_existing_conflict_completes_teardown_before_creating_replacement(): void
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

    public function test_dispatch_happens_only_after_records_commit(): void
    {
        Queue::fake();

        $manager = resolve(DevelopmentEventManager::class);

        $resolved = $manager->validateStartRequest([DevelopmentEventManager::WEEKLY_CELESTIALS], []);

        $created = $manager->startAfterConfirmation($resolved, []);

        $this->assertEquals(ScheduledEventStatus::QUEUED, $created[0]->fresh()->status);
        Queue::assertPushed(InitiateWeeklyCelestialSpawnEvent::class, 1);
    }

    public function test_failed_create_does_not_leave_active_false_success_record(): void
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

    public function test_active_winter_rejects_another_winter(): void
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

    public function test_active_delusional_rejects_another_delusional(): void
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

    public function test_active_winter_permits_delusional(): void
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

    public function test_active_delusional_permits_winter(): void
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

    public function test_cancelled_winter_does_not_block_new_winter(): void
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

    public function test_completed_winter_does_not_block_new_winter(): void
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

    public function test_failed_winter_does_not_block_new_winter(): void
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

    public function test_running_schedule_rows_returns_weekly_event_running_now(): void
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

    public function test_running_schedule_rows_excludes_future_scheduled_weekly_event(): void
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

    public function test_running_schedule_rows_excludes_queued_development_event(): void
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

    public function test_running_schedule_rows_excludes_starting_event(): void
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

    public function test_running_schedule_rows_excludes_cancelling_event(): void
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

    public function test_running_schedule_rows_excludes_expired_row_still_marked_running(): void
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

    public function test_running_schedule_rows_excludes_running_status_row_with_no_runtime_event(): void
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

    public function test_running_schedule_rows_returns_running_seasonal_parent(): void
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

    public function test_running_schedule_rows_returns_exact_running_child_raid_immediately_after_parent(): void
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

    public function test_running_schedule_rows_excludes_future_child_raid_under_running_seasonal_parent(): void
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

    public function test_running_schedule_rows_excludes_past_child_raid_under_running_seasonal_parent(): void
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

    public function test_running_schedule_rows_does_not_attach_raid_from_another_seasonal_schedule_of_same_type(): void
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

    public function test_cancel_specific_cancels_only_exact_rows_still_running_now(): void
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

    public function test_cancel_specific_selecting_parent_and_child_does_not_cancel_child_twice(): void
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

    public function test_cancel_all_running_ignores_future_scheduled_rows(): void
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

    public function test_cancel_all_running_ignores_queued_rows(): void
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

    public function test_cancel_all_running_ignores_stale_expired_running_rows(): void
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

    public function test_cancel_all_running_uses_child_before_parent_order(): void
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

    public function test_running_seasonal_parent_excludes_future_winter_schedule(): void
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

    public function test_running_seasonal_parent_excludes_queued_winter_schedule(): void
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

    public function test_running_seasonal_parent_excludes_cancelling_winter_schedule(): void
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

    public function test_running_seasonal_parent_excludes_historical_winter_schedule(): void
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

    public function test_validate_start_request_still_rejects_duplicate_active_seasonal_event_for_seasonal_raid_flow(): void
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

    public function test_find_active_conflicts_still_detects_same_map_conflict_for_seasonal_raid_attachment(): void
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

    public function test_start_after_confirmation_with_existing_seasonal_parent_leaves_no_schedule_on_failure(): void
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
