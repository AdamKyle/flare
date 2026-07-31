<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateScheduledEvent;

class ScheduledEventTest extends TestCase
{
    use CreateEvent, CreateScheduledEvent, RefreshDatabase;

    public function testRunningNowIncludesScheduleMatchingEveryRunningCondition(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->assertTrue(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesFutureScheduledEvent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesFutureQueuedEvent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::QUEUED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesStartingEvent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::STARTING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesCancellingEvent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::CANCELLING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesCancelledEvent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::CANCELLED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesCompletedEvent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::COMPLETED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesFailedEvent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::FAILED,
            'currently_running' => false,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesRunningStatusWithFutureStart(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(10),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesRunningStatusWithExpiredScheduledEnd(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(10),
            'end_date' => now()->subMinutes(5),
        ]);

        $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(5),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesRunningStatusWithNoRuntimeEvent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesRuntimeEventStartingInTheFuture(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->addMinutes(1),
            'ends_at' => now()->addMinutes(10),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesRuntimeEventThatHasEnded(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $scheduledEvent = $this->createScheduledEvent([
            'event_type' => EventType::WEEKLY_CELESTIALS,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->createEvent([
            'type' => EventType::WEEKLY_CELESTIALS,
            'scheduled_event_id' => $scheduledEvent->id,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->subMinutes(1),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($scheduledEvent->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }

    public function testRunningNowExcludesChildBelongingToNonRunningParent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 12:00:00'));

        $parent = $this->createScheduledEvent([
            'event_type' => EventType::WINTER_EVENT,
            'status' => ScheduledEventStatus::SCHEDULED,
            'currently_running' => false,
            'start_date' => now()->addMinutes(5),
            'end_date' => now()->addMinutes(10),
        ]);

        $child = $this->createScheduledEvent([
            'event_type' => EventType::RAID_EVENT,
            'parent_scheduled_event_id' => $parent->id,
            'status' => ScheduledEventStatus::RUNNING,
            'currently_running' => true,
            'start_date' => now()->subMinutes(5),
            'end_date' => now()->addMinutes(5),
        ]);

        $this->assertFalse(ScheduledEvent::whereKey($child->id)->runningNow(now())->exists());

        Carbon::setTestNow();
    }
}
