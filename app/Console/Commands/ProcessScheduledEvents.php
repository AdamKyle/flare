<?php

namespace App\Console\Commands;

use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\EventType;
use App\Game\Raids\Jobs\InitiateRaid;
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
    protected $description = 'Process and begin initiazling scheduled events.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         $targetEventStart = now()->copy()->addMinutes(5);

         $scheduledEvents = ScheduledEvent::whereBetween('start_date', [now(), $targetEventStart])->get();

         foreach ($scheduledEvents as $event) {
            $eventType = new EventType($event->event_type);

            if ($eventType->isRaidEvent()) {
                InitiateRaid::dispatch($event->id, $event->raid_id, preg_split('/(?<=[.!?])\s+/', $event->raid->story))->delay(now()->addMinutes(5));
            }
         }
    }
}
