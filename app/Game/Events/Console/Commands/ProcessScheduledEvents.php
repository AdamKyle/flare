<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\ScheduledEventDispatchService;
use App\Game\Events\Values\ScheduledEventStatus;
use Illuminate\Console\Command;

class ProcessScheduledEvents extends Command
{
    protected $signature = 'process:scheduled-events';

    protected $description = 'Process and begin initialing scheduled events.';

    public function handle(ScheduledEventDispatchService $scheduledEventDispatchService): void
    {
        $now = now();

        $eligibleScheduledEvents = ScheduledEvent::where('status', ScheduledEventStatus::SCHEDULED)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>', $now)
            ->get();

        foreach ($eligibleScheduledEvents as $scheduledEvent) {
            if (! is_null($scheduledEvent->parent_scheduled_event_id) && ! $this->parentIsRunning($scheduledEvent)) {
                continue;
            }

            $scheduledEventDispatchService->dispatch($scheduledEvent, $scheduledEvent->start_date);
        }
    }

    private function parentIsRunning(ScheduledEvent $scheduledEvent): bool
    {
        $parent = $scheduledEvent->parent;

        return ! is_null($parent) && $parent->status()->isRunning();
    }
}
