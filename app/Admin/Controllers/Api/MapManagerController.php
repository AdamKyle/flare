<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Requests\MoveLocationRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Game\Maps\Services\LocationService;
use App\Http\Controllers\Controller;
use Facades\App\Flare\Cache\CoordinatesCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MapManagerController extends Controller
{
    public function __construct(private readonly LocationService $locationService) {}

    public function getMapData(GameMap $gameMap): JsonResponse
    {

        $coordinates = CoordinatesCache::getFromCache();

        return response()->json([
            'path' => Storage::disk('maps')->url($gameMap->path),
            'x_coordinates' => $coordinates['x'],
            'y_coordinates' => $coordinates['y'],
            'locations' => $this->locationService->fetchLocationsForMap($gameMap),
        ]);
    }

    public function moveLocation(MoveLocationRequest $request, Location $location): JsonResponse
    {
        $location->update([
            'x' => $request->x,
            'y' => $request->y,
        ]);

        $location = $location->refresh();

        return response()->json([
            'locations' => $this->locationService->fetchLocationsForMap($location->map),
        ]);
    }
}
