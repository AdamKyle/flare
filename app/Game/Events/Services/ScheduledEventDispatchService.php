<?php

namespace App\Game\Events\Services;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use App\Game\Events\Jobs\InitiateWeeklyFactionLoyaltyEvent;
use App\Game\Events\Jobs\InitiateWinterEvent;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\Raids\Jobs\InitiateRaid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ScheduledEventDispatchService
{
    /**
     * Dispatch the job for the supported event type owned by this schedule to
     * become available at the given timestamp.
     *
     * Refuses schedules that are terminal, cancelling, queued, starting, or
     * running. Sets the schedule to queued immediately (so a fast queue's
     * initiation job status guard can pass), then pushes the job after the
     * durable status update succeeds.
     *
     * @return array{dispatched: bool, skipped_reason: ?string, status: string}
     */
    public function dispatch(ScheduledEvent $scheduledEvent, Carbon $availableAt): array
    {
        $status = $scheduledEvent->status();

        if (! $status->isDispatchable()) {
            return $this->skippedResult($status->value(), 'not_dispatchable');
        }

        $cacheKey = $this->cacheKeyFor($scheduledEvent);

        if (! Cache::add($cacheKey, true, now()->addMinutes(10))) {
            return $this->skippedResult($status->value(), 'duplicate');
        }

        $previousStatus = $scheduledEvent->status;

        try {
            $scheduledEvent->applyStatus(ScheduledEventStatus::QUEUED);

            $this->dispatchJobForType($scheduledEvent->fresh(), $availableAt);
        } catch (Throwable $throwable) {
            Cache::forget($cacheKey);

            $scheduledEvent->refresh();

            if ($scheduledEvent->status === ScheduledEventStatus::QUEUED) {
                $scheduledEvent->applyStatus($previousStatus);
            }

            throw $throwable;
        }

        return [
            'dispatched' => true,
            'skipped_reason' => null,
            'status' => ScheduledEventStatus::QUEUED,
        ];
    }

    private function dispatchJobForType(ScheduledEvent $scheduledEvent, Carbon $availableAt): void
    {
        $eventType = new EventType($scheduledEvent->event_type);

        if ($eventType->isRaidEvent()) {
            InitiateRaid::dispatch(
                $scheduledEvent->id,
                preg_split('/(?<=[.!?])\s+/', $scheduledEvent->raid->story)
            )->delay($availableAt);

            return;
        }

        if ($eventType->isWeeklyCelestials()) {
            InitiateWeeklyCelestialSpawnEvent::dispatch($scheduledEvent->id)->delay($availableAt);

            return;
        }

        if ($eventType->isWeeklyCurrencyDrops()) {
            InitiateWeeklyCurrencyDropEvent::dispatch($scheduledEvent->id)->delay($availableAt);

            return;
        }

        if ($eventType->isWeeklyFactionLoyaltyEvent()) {
            InitiateWeeklyFactionLoyaltyEvent::dispatch($scheduledEvent->id)->delay($availableAt);

            return;
        }

        if ($eventType->isWinterEvent()) {
            InitiateWinterEvent::dispatch($scheduledEvent->id)->delay($availableAt);

            return;
        }

        if ($eventType->isDelusionalMemoriesEvent()) {
            InitiateDelusionalMemoriesEvent::dispatch($scheduledEvent->id)->delay($availableAt);
        }
    }

    private function cacheKeyFor(ScheduledEvent $scheduledEvent): string
    {
        return 'scheduled-event-dispatch:'.$scheduledEvent->id;
    }

    private function skippedResult(string $status, string $reason): array
    {
        return [
            'dispatched' => false,
            'skipped_reason' => $reason,
            'status' => $status,
        ];
    }
}
