<?php

namespace App\Game\Maps\Adventure\Controllers\Api;

use Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Map;
use App\Flare\Models\Location;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\User;

class MapController extends Controller {

    public function __construct() {
        $this->middleware('auth:api');
    }

    public function index(Request $request, User $user) {
        return response()->json([
            'map_url' => asset('/maps/surface.png'),
            'character_map' => $user->character->map,
            'character_id'  => $user->character->id,
            'locations'     => Location::all(),
            'can_move'      => $user->character->can_move,
            'show_message'  => $user->character->can_move ? false : true,
        ]);
    }

    public function move(Request $request, Character $character) {

        $character->map->update([
            'character_position_x' => $request->character_position_x,
            'character_position_y' => $request->character_position_y,
            'position_x'           => $request->position_x,
            'position_y'           => $request->position_y,
        ]);

        $character->update(['can_move' => false]);

        event(new MoveTimeOutEvent($character));

        return response()->json([], 200);
    }

    public function isWater(Request $request, Character $character) {
        // return response()->json([], 200);
        $contents            = Storage::disk('public')->get('surface.png');
        $this->imageResource = imagecreatefromstring($contents);

        $waterRgb = 112219255;
        $rgb      = imagecolorat($this->imageResource, $request->character_position_x, $request->character_position_y);

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        $color = $r.$g.$b;

        if ((int) $color === $waterRgb) {
            // TODO: Implement loic to check for a relic called: 'flask of fresh air'.
            return response()->json([], 422);
        }

        return response()->json([], 200);
    }
}
