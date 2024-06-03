<?php

namespace App\Admin\Controllers\Api;

use App\Game\Maps\Services\LocationService;
use Facades\App\Flare\Cache\CoordinatesCache;
use App\Flare\Models\GameMap;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MapManagerController extends Controller {

    public function __construct(private readonly LocationService $locationService){}

    /**
     * @param GameMap $gameMap
     * @return JsonResponse
     */
    public function getMapData(GameMap $gameMap): JsonResponse {

        $coordinates = CoordinatesCache::getFromCache();

        return response()->json([
            'path' => Storage::disk('maps')->url($gameMap->path),
            'x_coordinates' => $coordinates['x'],
            'y_coordinates' => $coordinates['y'],
            'locations' => $this->locationService->fetchLocationsForMap($gameMap)
        ]);
    }
}
