<?php

namespace Tests\Unit\Game\Events\Services;

use App\Flare\Models\Monster;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use App\Game\Events\Jobs\InitiateWeeklyFactionLoyaltyEvent;
use App\Game\Events\Jobs\InitiateWinterEvent;
use App\Game\Events\Services\ScheduledEventDispatchService;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Raids\Jobs\InitiateRaid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRaid;
use Tests\Traits\CreateScheduledEvent;

class ScheduledEventDispatchServiceTest extends TestCase
{
    use CreateGameMap, CreateLocation, CreateRaid, CreateScheduledEvent, RefreshDatabase;

    public function test_weekly_celestials_dispatches_exact_job(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::WEEKLY_CELESTIALS]);

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        Queue::assertPushed(InitiateWeeklyCelestialSpawnEvent::class, 1);
    }

    public function test_weekly_currency_drops_dispatches_exact_job(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::WEEKLY_CURRENCY_DROPS]);

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        Queue::assertPushed(InitiateWeeklyCurrencyDropEvent::class, 1);
    }

    public function test_weekly_faction_loyalty_dispatches_exact_job(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::WEEKLY_FACTION_LOYALTY_EVENT]);

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        Queue::assertPushed(InitiateWeeklyFactionLoyaltyEvent::class, 1);
    }

    public function test_winter_event_dispatches_exact_job(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT]);

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        Queue::assertPushed(InitiateWinterEvent::class, 1);
    }

    public function test_delusional_memories_event_dispatches_exact_job(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT]);

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        Queue::assertPushed(InitiateDelusionalMemoriesEvent::class, 1);
    }

    public function test_raid_event_dispatches_exact_job(): void
    {
        Queue::fake();

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'story' => 'One. Two.',
        ]);

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::RAID_EVENT, 'raid_id' => $raid->id]);

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        Queue::assertPushed(InitiateRaid::class, 1);
    }

    public function test_raid_dispatch_retains_story_arguments(): void
    {
        config(['queue.default' => 'sync']);
        Event::fake([GlobalMessageEvent::class]);

        $gameMap = $this->createGameMap();
        $location = $this->createLocation(['game_map_id' => $gameMap->id]);
        $raid = $this->createRaid([
            'raid_boss_id' => Monster::factory()->create()->id,
            'raid_boss_location_id' => $location->id,
            'corrupted_location_ids' => [],
            'story' => 'One. Two.',
        ]);

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::RAID_EVENT, 'raid_id' => $raid->id]);

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        Event::assertDispatched(GlobalMessageEvent::class, function ($event) {
            return $event->message === 'One.';
        });
    }

    public function test_delayed_availability_equals_supplied_timestamp(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::WEEKLY_CELESTIALS]);
        $availableAt = now()->addMinute();

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, $availableAt);

        Queue::assertPushed(InitiateWeeklyCelestialSpawnEvent::class, function ($job) use ($availableAt) {
            return $job->delay instanceof \DateTimeInterface && $job->delay->getTimestamp() === $availableAt->getTimestamp();
        });
    }

    public function test_dispatched_schedule_becomes_queued(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::WEEKLY_CELESTIALS]);

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        $this->assertEquals(ScheduledEventStatus::QUEUED, $scheduledEvent->fresh()->status);
    }

    public function test_duplicate_dispatch_is_skipped_by_cache_key(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent(['event_type' => EventType::WEEKLY_CELESTIALS]);

        $service = new ScheduledEventDispatchService();
        $service->dispatch($scheduledEvent, now()->addMinute());
        $scheduledEvent->update(['status' => ScheduledEventStatus::SCHEDULED]);
        $result = $service->dispatch($scheduledEvent, now()->addMinute());

        $this->assertFalse($result['dispatched']);
        Queue::assertPushed(InitiateWeeklyCelestialSpawnEvent::class, 1);
    }

    public function test_queued_schedule_is_skipped(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::QUEUED,
        ]);

        $result = (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        $this->assertFalse($result['dispatched']);
        Queue::assertNotPushed(InitiateWeeklyCelestialSpawnEvent::class);
    }

    public function test_starting_schedule_is_skipped(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::STARTING,
        ]);

        $result = (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        $this->assertFalse($result['dispatched']);
        Queue::assertNotPushed(InitiateWeeklyCelestialSpawnEvent::class);
    }

    public function test_running_schedule_is_skipped(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
        ]);

        $result = (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        $this->assertFalse($result['dispatched']);
        Queue::assertNotPushed(InitiateWeeklyCelestialSpawnEvent::class);
    }

    public function test_cancelling_schedule_is_skipped(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::CANCELLING,
        ]);

        $result = (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        $this->assertFalse($result['dispatched']);
        Queue::assertNotPushed(InitiateWeeklyCelestialSpawnEvent::class);
    }

    public function test_terminal_schedule_is_skipped(): void
    {
        Queue::fake();

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::CANCELLED,
        ]);

        $result = (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());

        $this->assertFalse($result['dispatched']);
        Queue::assertNotPushed(InitiateWeeklyCelestialSpawnEvent::class);
    }

    public function test_dispatch_throws_when_event_type_is_invalid(): void
    {
        $scheduledEvent = $this->createScheduledEvent(['event_type' => 999999]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('999999 does not exist.');

        (new ScheduledEventDispatchService())->dispatch($scheduledEvent, now()->addMinute());
    }
}
