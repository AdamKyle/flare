<?php

namespace App\Game\Character\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Game\Core\Values\FactionLevel;
use Illuminate\Console\Command;

class AssignNewFactionsToCharacters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:new-factions-to-characters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns New Factions to Characters';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        Character::chunkById(250, function ($characters) {
            foreach ($characters as $character) {
                foreach (GameMap::all() as $gameMap) {

                    if ($gameMap->mapType()->isPurgatory()) {
                        continue;
                    }

                    $faction = $character->factions()->where('game_map_id', $gameMap->id)->first();

                    if (! is_null($faction)) {
                        continue;
                    }

                    $character->factions()->create([
                        'character_id' => $character->id,
                        'game_map_id' => $gameMap->id,
                        'points_needed' => FactionLevel::getPointsNeeded(0),
                    ]);
                }
            }
        });
    }
}
