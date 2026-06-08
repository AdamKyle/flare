<?php

namespace App\Game\Events\Console\Commands;

use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Jobs\InitiateDelusionalMemoriesEvent;
use App\Game\Events\Jobs\InitiateWeeklyCelestialSpawnEvent;
use App\Game\Events\Jobs\InitiateWeeklyCurrencyDropEvent;
use App\Game\Events\Jobs\InitiateWeeklyFactionLoyaltyEvent;
use App\Game\Events\Jobs\InitiateWinterEvent;
use App\Game\Events\Values\EventType;
use App\Game\Raids\Jobs\InitiateRaid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

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
    public function handle()
    {
        $now = now();
        $targetEventStart = $now->copy()->addMinutes(5);

        $scheduledEvents = ScheduledEvent::where('currently_running', false)
            ->where(function ($query) use ($now, $targetEventStart) {
                $query->where(function ($inner) use ($now, $targetEventStart) {
                    $inner->where('start_date', '>', $now)
                        ->where('start_date', '<=', $targetEventStart);
                })->orWhere(function ($inner) use ($now) {
                    $inner->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now);
                });
            })
            ->get();

        foreach ($scheduledEvents as $event) {
            $cacheKey = 'scheduled-event-dispatch:'.$event->id;

            $cacheTtl = now()->addMinutes(10);

            if (! Cache::add($cacheKey, true, $cacheTtl)) {
                continue;
            }

            try {
                $eventType = new EventType($event->event_type);

                if ($eventType->isRaidEvent()) {
                    InitiateRaid::dispatch($event->id, preg_split('/(?<=[.!?])\s+/', $event->raid->story))->delay($now->copy()->addMinutes(5));
                }

                if ($eventType->isWeeklyCelestials()) {
                    InitiateWeeklyCelestialSpawnEvent::dispatch($event->id)->delay($now->copy()->addMinutes(5));
                }

                if ($eventType->isWeeklyCurrencyDrops()) {
                    InitiateWeeklyCurrencyDropEvent::dispatch($event->id)->delay($now->copy()->addMinutes(5));
                }

                if ($eventType->isWinterEvent()) {
                    InitiateWinterEvent::dispatch($event->id)->delay($now->copy()->addMinutes(5));
                }

                if ($eventType->isDelusionalMemoriesEvent()) {
                    InitiateDelusionalMemoriesEvent::dispatch($event->id)->delay($now->copy()->addMinutes(5));
                }

                if ($eventType->isWeeklyFactionLoyaltyEvent()) {
                    InitiateWeeklyFactionLoyaltyEvent::dispatch($event->id)->delay($now->copy()->addMinutes(5));
                }
            } catch (Throwable $throwable) {
                Cache::forget($cacheKey);

                throw $throwable;
            }
        }
    }
}
