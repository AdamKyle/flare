<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Game\Core\Values\FactionLevel;
use Illuminate\Console\Command;

/**
 * @codeCoverageIgnore
 */
class AssignFactionsToAllCharacters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:factions-to-characters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives Factions To All Characters';

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
       Character::chunkById(100, function($characters) {
           foreach ($characters as $character) {
               $this->assignFactions($character);
           }
       });
    }

    public function assignFactions(Character $character) {
        $gameMaps = GameMap::all();

        foreach ($gameMaps as $gameMap) {
            $faction = $character->factions()->where('game_map_id', $gameMap->id)->first();

            if (is_null($faction) && !$gameMap->mapType()->isPurgatory()) {
                $character->factions()->create([
                    'character_id' => $character->id,
                    'game_map_id' => $gameMap->id,
                    'points_needed' => FactionLevel::getPointsNeeded(0),
                ]);
            }
        }
    }
}
