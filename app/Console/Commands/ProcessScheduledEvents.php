<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\EventType;
use App\Game\Events\Jobs\InitiateMonthlyPVPEvent;
use App\Game\Raids\Jobs\InitiateRaid;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use App\Game\Events\Jobs\InitiateWinterEvent;

class ProcessScheduledEvents extends Command {
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
    public function handle() {
        $targetEventStart = now()->copy()->addMinutes(5);

        $scheduledEvents = ScheduledEvent::where('start_date', '>=', now())
            ->where('start_date', '<=', $targetEventStart)
            ->get();


        foreach ($scheduledEvents as $event) {
            $eventType = new EventType($event->event_type);

            if ($eventType->isRaidEvent()) {
                InitiateRaid::dispatch($event->id, preg_split('/(?<=[.!?])\s+/', $event->raid->story))->delay(now()->addMinutes(5));
            }

            if ($eventType->isWeeklyCelestials()) {
                InitiateWeeklyCelestialSpawnEvent::dispatch($event->id)->delay(now()->addMinutes(5));
            }

            if ($eventType->isWeeklyCurrencyDrops()) {
                InitiateWeeklyCurrencyDropEvent::dispatch($event->id)->delay(now()->addMinutes(5));
            }

            if ($eventType->isMonthlyPVP()) {
                InitiateMonthlyPVPEvent::dispatch($event->id)->delay(now()->addMinutes(5));
            }

            if ($eventType->isWinterEvent()) {
                InitiateWinterEvent::dispatch($event->id)->delay(now()->addMinutes(5));
            }
        }
    }
}
