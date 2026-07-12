<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\EventLifecycleService;
use Illuminate\Console\Command;

class EndScheduledEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'end:scheduled-event {eventId?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End all scheduled events';

    /**
     * With no eventId, naturally completes every active schedule whose
     * runtime event has actually ended. With an explicit eventId, forces
     * immediate manual cancellation of that exact schedule.
     */
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
