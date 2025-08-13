<?php

namespace App\Game\Events\Console\Commands;


use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Registry\EventEnderRegistry;
use App\Game\Events\Services\ScheduleEventFinalizerService;
use App\Game\Events\Values\EventType;
use Exception;
use Illuminate\Console\Command;

class EndScheduledEvent extends Command
{
    protected $signature = 'end:scheduled-event';

    protected $description = 'End all scheduled events';

    /**
     * @param  EventEnderRegistry  $registry
     * @param  ScheduleEventFinalizerService  $finalizer
     * @return void
     *
     * @throws Exception
     */
    public function handle(
        EventEnderRegistry $registry,
        ScheduleEventFinalizerService $finalizer
    ): void {
        $scheduled = ScheduledEvent::query()
            ->where('end_date', '<=', now())
            ->where('currently_running', true)
            ->get();

        if ($scheduled->isEmpty()) {
            return;
        }

        foreach ($scheduled as $scheduledEvent) {
            $currentEvent = Event::query()
                ->where('type', $scheduledEvent->event_type)
                ->where('ends_at', '<=', now())
                ->first();

            if (is_null($currentEvent)) {
                $finalizer->markNotRunningAndBroadcast($scheduledEvent);
                continue;
            }

            $eventType = new EventType($scheduledEvent->event_type);

            $registry->end($eventType, $scheduledEvent, $currentEvent);

            $finalizer->markNotRunningAndBroadcast($scheduledEvent);
        }
    }
}
