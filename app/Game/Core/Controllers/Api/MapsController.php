<?php

namespace App\Game\Core\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;

class MapsController extends Controller {

    public function __construct() {
        $this->middleware('auth:api');
    }

    public function index(Character $character) {
        $maps = GameMap::where('id', '!=', $character->map->game_map_id)->get();

        $mapInfo = [];

        foreach ($maps as $map) {
            $mapInfo[] = [
                'name' => $map->name,
                'id'   => $map->id,
            ];
        }

        return response()->json([
            'maps'        => $mapInfo,
            'current_map' => $character->map->gameMap->name,
        ]);
    }
}
