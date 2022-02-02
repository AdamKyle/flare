<?php

namespace App\Console\Commands;

use App\Flare\Models\Faction;
use Illuminate\Console\Command;

class ReduceFactionPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reduce:faction-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'While auto battle is not working this can reduce the faction points a player has.';

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
     *
     * @return int
     */
    public function handle()
    {
        Faction::ChunkById(500, function($factions) {
           foreach ($factions as $faction) {

               $newCurrentPoints = $faction->current_points;

               $newPointsNeeded = $faction->points_needed / 10;

               if ($faction->current_points > $newPointsNeeded) {
                   $newCurrentPoints = $faction->current_points / 10;
               }

               $faction->update([
                   'current_points' => $newCurrentPoints,
                   'points_needed'  => $newPointsNeeded,
               ]);
           }
        });
    }
}
