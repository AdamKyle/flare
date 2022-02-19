<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Game\Core\Values\FactionLevel;
use Illuminate\Console\Command;

/**
 * @codeCoverageIgnore
 */
class UpdateFactionPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:faction-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates characters faction points';

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
        Character::chunkById(250, function($characters) {
            foreach ($characters as $character) {
                $this->updateFactions($character);
            }
        });
    }

    /**
     * Update the characters Faction Points.
     *
     * @param Character $character
     * @return void
     */
    protected function updateFactions(Character $character) {
        foreach ($character->factions as $faction) {
            if (FactionLevel::isMaxLevel($faction->current_level)) {
                continue;
            }

            $currentPoints = $faction->current_points;

            if (log10($faction->points_need) < 3) {
                if (log10($currentPoints) < 3) {
                    $currentPoints *= 10;
                }
            }

            $faction->update([
                'current_points' => $currentPoints,
                'points_needed'  => FactionLevel::getPointsNeeded($faction->current_level)
            ]);
        }
    }
}
