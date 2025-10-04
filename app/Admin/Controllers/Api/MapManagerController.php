<?php

namespace App\Admin\Controllers\Api;

use App\Admin\Requests\MoveLocationRequest;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Npc;
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
            'npcs' => Npc::where('game_map_id', $gameMap->id)->get(),
        ]);
    }

    public function moveLocation(MoveLocationRequest $request, GameMap $gameMap): JsonResponse
    {
        if ($request->location_id > 0) {
            $location = Location::find($request->location_id);

            $location->update([
                'x' => $request->x,
                'y' => $request->y,
            ]);
        }

        if ($request->npc_id > 0) {
            $npc = Npc::find($request->npc_id);

            $npc->update([
                'x_position' => $request->x,
                'y_position' => $request->y,
            ]);
        }

        return response()->json([
            'locations' => $this->locationService->fetchLocationsForMap($gameMap),
            'npcs' => Npc::where('game_map_id', $gameMap->id)->get(),
        ]);
    }
}
