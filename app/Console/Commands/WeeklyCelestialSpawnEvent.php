<?php

namespace App\Console\Commands;

use App\Flare\Jobs\DailyGoldDustJob;
use App\Flare\Models\Character;
use Illuminate\Console\Command;
use Facades\App\Flare\Values\UserOnlineValue;

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

        WeeklyCelestialSpawnEvent::dispatch()->delay(now()->addMinutes(10)/*now()->addHours(2)*/);
    }
}
