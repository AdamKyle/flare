<?php

namespace App\Game\Battle\Console\Commands;

use App\Flare\Models\CelestialFight;
use App\Flare\Models\CharacterInCelestialFight;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Console\Command;

class ClearCelestials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:celestials';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears celestials older than an hour';

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
    public function handle(): void
    {
        $count = CelestialFight::whereDate('updated_at', '<=', now()->subHour())->count();

        if ($count > 0) {
            CelestialFight::where('updated_at', '<=', now()->subHour())->chunkById(100, function ($fights) {
                foreach ($fights as $fight) {
                    CharacterInCelestialFight::where('celestial_fight_id', $fight->id)->delete();

                    $monster = $fight->monster;

                    event(new GlobalMessageEvent($monster->name.' has vanished from the '.$monster->gameMap->name.' plane (hourly reset).'));

                    $fight->delete();
                }
            });
        }
    }
}
