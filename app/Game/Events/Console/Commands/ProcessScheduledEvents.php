<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\ScheduledEventDispatchService;
use App\Game\Events\Values\ScheduledEventStatus;
use Illuminate\Console\Command;

class ProcessScheduledEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:scheduled-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and begin initialing scheduled events.';

    /**
     * Execute the console command.
     */
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

    /**
     * A seasonal child raid may only be dispatched once its parent is running.
     */
    private function parentIsRunning(ScheduledEvent $scheduledEvent): bool
    {
        $parent = $scheduledEvent->parent;

        return ! is_null($parent) && $parent->status()->isRunning();
    }
}
