<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Game\Maps\Services\LocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CreateLocationDataCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:location-data-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Created location cache based on game map.';

    /**
     * Execute the console command.
     */
    public function handle(LocationService $locationService)
    {
        $gameMaps = GameMap::all();

        foreach ($gameMaps as $gameMap) {
            $cacheKey = 'map-locations-'.$gameMap->id;

            Cache::delete($cacheKey);

            $locationService->fetchLocationData($gameMap->id);
        }
    }
}
