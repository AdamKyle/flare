<?php

namespace App\Console\Commands;

use App\Flare\Models\Event;
use App\Flare\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use Cache;
use Illuminate\Console\Command;
use App\Flare\Jobs\WeeklyCelestialSpawnEvent as SpawnCancelingJob;

class WeeklyCelestialSpawnEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:celestial-spawn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes celestials spawn 80% more once a week for a couple hours.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle() {

        Cache::put('celestial-spawn-rate', .8);
        Cache::put('celestial-event-date', now()->addDay());

        Event::create([
            'type'       => EventType::WEEKLY_CELESTIALS,
            'started_at' => now(),
            'ends_at'    => now()->addDay()
        ]);

        SpawnCancelingJob::dispatch()->delay(now()->addMinutes(15))->onConnection('weekly_spawn');

        event(new GlobalMessageEvent('The gates have swung open and the Celestial\'s are free.
        get your weapons ready! (Celestials have a 80% chance to spawn regardless of plane based on any
        movement type except traverse - for the next 24 hours! Only one will spawn per hour unless it is killed.)'
        ));
    }
}
