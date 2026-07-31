<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\EventLifecycleService;
use Illuminate\Console\Command;

class EndScheduledEvent extends Command
{
    protected $signature = 'end:scheduled-event {eventId?}';

    protected $description = 'End all scheduled events';

    public function handle(EventLifecycleService $eventLifecycleService): void
    {
        $eventId = $this->argument('eventId');

        if (is_null($eventId)) {
            $eventLifecycleService->completeNaturallyExpired();

            return;
        }

        $scheduledEvent = ScheduledEvent::find($eventId);

        if (is_null($scheduledEvent)) {
            return;
        }

        $eventLifecycleService->cancel($scheduledEvent);
    }
}
