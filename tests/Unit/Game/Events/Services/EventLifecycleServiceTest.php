<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Event;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Models\RaidBoss;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Services\EventLifecycleService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class EventLifecycleServiceTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    public function test_manual_cancellation_ignores_future_runtime_end_time_and_fully_tears_down(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
            'scheduled_event_id' => $scheduledEvent->id,
        ]);

        resolve(EventLifecycleService::class)->cancel($scheduledEvent);

        $this->assertEquals(0, Event::where('scheduled_event_id', $scheduledEvent->id)->count());
        $this->assertEquals(ScheduledEventStatus::CANCELLED, $scheduledEvent->fresh()->status);
    }

    public function test_queued_schedule_cancellation_clears_it_and_delayed_job_later_no_ops(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::QUEUED,
        ]);

        resolve(EventLifecycleService::class)->cancel($scheduledEvent);

        $scheduledEvent = $scheduledEvent->fresh();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $scheduledEvent->status);

        InitiateWeeklyCelestialSpawnEvent::dispatchSync($scheduledEvent->id);

        $this->assertEquals(0, Event::where('scheduled_event_id', $scheduledEvent->id)->count());
    }

    public function test_exact_runtime_event_is_selected_by_scheduled_event_id(): void
    {
        $scheduledEventOne = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        $scheduledEventTwo = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
            'scheduled_event_id' => $scheduledEventOne->id,
        ]);

        $eventTwo = Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
            'scheduled_event_id' => $scheduledEventTwo->id,
        ]);

        resolve(EventLifecycleService::class)->cancel($scheduledEventTwo);

        $this->assertEquals(0, Event::where('id', $eventTwo->id)->count());
        $this->assertEquals(ScheduledEventStatus::RUNNING, $scheduledEventOne->fresh()->status);
    }

    public function test_ambiguous_legacy_fallback_throws_rather_than_choosing_first(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
        ]);

        $this->expectException(RuntimeException::class);

        resolve(EventLifecycleService::class)->cancel($scheduledEvent);
    }

    public function test_one_different_map_raid_cancellation_leaves_other_raid_active(): void
    {
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

        $scheduleOne = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidOne->id,
            'status' => ScheduledEventStatus::QUEUED,
        ]);

        $scheduleTwo = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raidTwo->id,
            'status' => ScheduledEventStatus::QUEUED,
        ]);

        resolve(EventLifecycleService::class)->cancel($scheduleOne);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $scheduleOne->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::QUEUED, $scheduleTwo->fresh()->status);
    }

    public function test_child_raid_cancellation_leaves_parent_active(): void
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
        ]);

        $child = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::QUEUED,
        ]);

        resolve(EventLifecycleService::class)->cancel($child);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $child->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::RUNNING, $parent->fresh()->status);
    }

    public function test_natural_end_marks_completed_not_cancelled(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
            'scheduled_event_id' => $scheduledEvent->id,
        ]);

        resolve(EventLifecycleService::class)->completeNaturallyExpired();

        $scheduledEvent = $scheduledEvent->fresh();

        $this->assertEquals(ScheduledEventStatus::COMPLETED, $scheduledEvent->status);
        $this->assertNull($scheduledEvent->cancelled_at);
    }

    public function test_manual_end_marks_cancelled_and_sets_cancelled_timestamp(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
            'scheduled_event_id' => $scheduledEvent->id,
        ]);

        resolve(EventLifecycleService::class)->cancel($scheduledEvent);

        $scheduledEvent = $scheduledEvent->fresh();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $scheduledEvent->status);
        $this->assertNotNull($scheduledEvent->cancelled_at);
    }

    public function test_no_global_event_table_is_truncated(): void
    {
        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
        ]);

        Event::create([
            'type' => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at' => now()->addDay(),
            'scheduled_event_id' => $scheduledEvent->id,
        ]);

        $unrelatedGoal = GlobalEventGoal::factory()->create([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        resolve(EventLifecycleService::class)->cancel($scheduledEvent);

        $this->assertEquals(1, GlobalEventGoal::where('id', $unrelatedGoal->id)->count());
    }

    public function test_cancelling_running_winter_event_ends_running_child_raid_with_normal_teardown_before_parent(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $parentEvent = Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'is_corrupted' => true,
            'has_raid_boss' => true,
        ]);

        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $location->update(['raid_id' => $raid->id]);

        $raidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $raid->raid_boss_id,
            'boss_max_hp' => 100,
            'boss_current_hp' => 100,
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

        $childEvent = Event::create([
            'type' => EventType::RAID_EVENT,
            'scheduled_event_id' => $child->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        resolve(EventLifecycleService::class)->cancel($parent);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $child->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::CANCELLED, $parent->fresh()->status);

        $this->assertEquals(0, RaidBoss::where('raid_id', $raid->id)->count());
        $this->assertFalse(Location::find($location->id)->is_corrupted);
        $this->assertFalse(Location::find($location->id)->has_raid_boss);

        $this->assertEquals(0, Event::where('id', $childEvent->id)->count());
        $this->assertEquals(0, Event::where('id', $parentEvent->id)->count());
    }

    public function test_cancelling_running_delusional_event_ends_running_child_raid_before_parent(): void
    {
        $this->createGameMap(['name' => MapNameValue::DELUSIONAL_MEMORIES]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $parent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
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

        resolve(EventLifecycleService::class)->cancel($parent);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $child->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::CANCELLED, $parent->fresh()->status);
    }

    public function test_running_child_belonging_to_another_seasonal_schedule_is_untouched(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

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

        $parentTwo = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $parentTwo->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $gameMapOne = $this->createGameMap(['name' => 'Ice Plane']);
        $gameMapTwo = $this->createGameMap(['name' => 'Delusional Memories']);
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

        resolve(EventLifecycleService::class)->cancel($parentOne);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $childOne->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::RUNNING, $childTwo->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::RUNNING, $parentTwo->fresh()->status);
    }

    public function test_future_child_under_cancelled_parent_does_not_receive_raid_teardown(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

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

        $gameMap = $this->createGameMap();
        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'is_corrupted' => true,
            'has_raid_boss' => true,
        ]);

        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
        ]);

        $location->update(['raid_id' => $raid->id]);

        $raidBoss = RaidBoss::create([
            'raid_id' => $raid->id,
            'raid_boss_id' => $raid->raid_boss_id,
            'boss_max_hp' => 100,
            'boss_current_hp' => 100,
        ]);

        $futureChild = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'raid_id' => $raid->id,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        resolve(EventLifecycleService::class)->cancel($parent);

        $this->assertEquals(1, RaidBoss::where('id', $raidBoss->id)->count());
        $this->assertTrue(Location::find($location->id)->is_corrupted);
        $this->assertTrue(Location::find($location->id)->has_raid_boss);
    }

    public function test_future_child_under_cancelled_parent_is_marked_cancelled_so_it_cannot_start_later(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

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

        $futureChild = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        resolve(EventLifecycleService::class)->cancel($parent);

        $futureChild = $futureChild->fresh();

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $futureChild->status);
        $this->assertNotNull($futureChild->cancelled_at);
    }

    public function test_completed_child_remains_unchanged_when_parent_cancelled(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

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

        $completedChild = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::COMPLETED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(20),
            'end_date' => now()->subMinutes(15),
        ]);

        resolve(EventLifecycleService::class)->cancel($parent);

        $this->assertEquals(ScheduledEventStatus::COMPLETED, $completedChild->fresh()->status);
    }

    public function test_cancelled_child_remains_unchanged_when_parent_cancelled(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

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

        $cancelledChild = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::CANCELLED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(20),
            'end_date' => now()->subMinutes(15),
            'cancelled_at' => now()->subMinutes(15),
        ]);

        resolve(EventLifecycleService::class)->cancel($parent);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $cancelledChild->fresh()->status);
        $this->assertEquals($cancelledChild->cancelled_at->timestamp, $cancelledChild->fresh()->cancelled_at->timestamp);
    }

    public function test_failed_child_remains_unchanged_when_parent_cancelled(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

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

        $failedChild = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::FAILED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(20),
            'end_date' => now()->subMinutes(15),
        ]);

        resolve(EventLifecycleService::class)->cancel($parent);

        $this->assertEquals(ScheduledEventStatus::FAILED, $failedChild->fresh()->status);
    }

    public function test_running_child_teardown_failure_prevents_parent_teardown(): void
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

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
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

        $child->update(['raid_id' => null]);

        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Attempt to read property "name" on null');

        resolve(EventLifecycleService::class)->cancel($parent);
    }

    public function test_cancelling_one_winter_instance_does_not_end_concurrent_delusional_instance(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

        $winterParent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $winterParent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $delusionalParent = $this->createScheduledEvent([
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        Event::create([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $delusionalParent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        resolve(EventLifecycleService::class)->cancel($winterParent);

        $this->assertEquals(ScheduledEventStatus::CANCELLED, $winterParent->fresh()->status);
        $this->assertEquals(ScheduledEventStatus::RUNNING, $delusionalParent->fresh()->status);
    }

    public function test_no_global_event_table_is_truncated_during_seasonal_cascade(): void
    {
        $this->createGameMap(['name' => MapNameValue::ICE_PLANE]);
        $this->createGameMap(['name' => MapNameValue::SURFACE]);

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

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
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

        $unrelatedGoal = GlobalEventGoal::factory()->create([
            'event_type' => EventType::WEEKLY_CELESTIALS,
        ]);

        resolve(EventLifecycleService::class)->cancel($parent);

        $this->assertEquals(1, GlobalEventGoal::where('id', $unrelatedGoal->id)->count());
    }
}
