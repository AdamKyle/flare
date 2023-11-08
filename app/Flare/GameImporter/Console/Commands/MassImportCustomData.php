<?php

namespace App\Flare\GameImporter\Console\Commands;

use App\Flare\Models\GameMap;
use App\Game\Events\Values\EventType;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MassImportCustomData extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mass:import-game-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Game Data in a specific way defined by the programmer';

    /**
     * Execute the console command.
     */
    public function handle() {

        // Handle importing things in a custom format.

        if (GameMap::where('only_for_event', EventType::WINTER_EVENT)->count() <= 0) {
            throw Exception('No map for this type of import was uploaded. Upload the map first.');
        }

        Artisan::call('import:game-data Items');
        Artisan::call('import:game-data Skills');
        Artisan::call('import:game-data Monsters');
        Artisan::call('import:game-data NPCs');
        Artisan::call('import:game-data Quests');

        Artisan::call('generate:monster-cache');

    }
}
