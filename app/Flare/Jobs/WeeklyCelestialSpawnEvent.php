<?php

namespace App\Flare\Jobs;

use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class WeeklyCelestialSpawnEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    public function handle() {

        if (Cache::has('celestial-event-date') && Cache::has('celestial-spawn-rate')) {
            if (now()->isAfter(Cache::get('celestial-event-date'))) {
                Cache::delete('celestial-spawn-rate');
                Cache::delete('celestial-event-date');

                event(new GlobalMessageEvent('
                The Creator has managed to get the celestial gates under control!
                The Celestials have been locked away again! Come back next Wednesday!
                '));
            } else {
                WeeklyCelestialSpawnEvent::dispatch()->delay(now()->addMinutes(15))->onConnection('weekly_spawn');
            }
        } else {
            event(new GlobalMessageEvent('The celestial gates have slammed shut early.'));
        }
    }
}
