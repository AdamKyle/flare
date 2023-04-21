<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Location;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;

class MapsController extends Controller {

    public function index(Character $character) {
        $maps = GameMap::where('id', '!=', $character->map->game_map_id)->get();

        $mapInfo = [];

        foreach ($maps as $map) {

            if ($map->mapType()->isPurgatory()) {
                $location = $this->getLocation($character);

                if (!is_null($location)) {
                    if ($map->required_location_id === $location->id) {
                        $mapInfo[] = [
                            'name' => $map->name,
                            'id'   => $map->id,
                        ];
                    }
                }
            } else {
                $mapInfo[] = [
                    'name' => $map->name,
                    'id'   => $map->id,
                ];
            }
        }

        return response()->json([
            'maps'        => $mapInfo,
            'current_map' => $character->map->gameMap->name,
        ]);
    }

    protected function getLocation($character): ?Location {
        return Location::where('x', $character->map->character_position_x)
                       ->where('y', $character->map->character_position_y)
                       ->where('game_map_id', $character->map->gameMap->id)
                       ->first();
    }
}
