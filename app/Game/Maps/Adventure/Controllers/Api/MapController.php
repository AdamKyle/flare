<?php

namespace App\Game\Maps\Adventure\Controllers\Api;

use Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Flare\Models\Map;
use App\Flare\Models\Location;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Requests\SetSailValidation;
use App\Game\Maps\Adventure\Services\PortService;
use App\User;
use Carbon\Carbon;

class MapController extends Controller {

    private $portService;

    public function __construct(PortService $portService) {

        $this->portService = $portService;

        $this->middleware('auth:api');
    }

    public function index(Request $request, User $user) {
        $port        = Location::where('x', $user->character->map->character_position_x)->where('y', $user->character->map->character_position_y)->where('is_port', true)->first();
        $portDetails = null;

        if (!is_null($port)) {
            $portDetails = $this->portService->getPortDetails($user->character, $port);
        }

        return response()->json([
            'map_url'       => Storage::disk('maps')->url($user->character->map->gameMap->path),
            'character_map' => $user->character->map,
            'character_id'  => $user->character->id,
            'locations'     => Location::all(),
            'can_move'      => $user->character->can_move,
            'timeout'       => $user->character->can_move_again_at,
            'show_message'  => $user->character->can_move ? false : true,
            'port_details'  => $portDetails,
        ]);
    }

    public function move(Request $request, Character $character) {

        $character->map->update([
            'character_position_x' => $request->character_position_x,
            'character_position_y' => $request->character_position_y,
            'position_x'           => $request->position_x,
            'position_y'           => $request->position_y,
        ]);

        $port        = Location::where('x', $request->character_position_x)->where('y', $request->character_position_y)->where('is_port', true)->first();
        
        $portDetails = [];

        if (!is_null($port)) {
            $portDetails = $this->portService->getPortDetails($character, $port);
        }

        $character->update([
            'can_move'          => false,
            'can_move_again_at' => now()->addSeconds(10),
        ]);

        event(new MoveTimeOutEvent($character));

        return response()->json($portDetails, 200);
    }

    public function setSail(SetSailValidation $request, Location $location, Character $character) {
        $fromPort = Location::where('id', $request->current_port_id)->where('is_port', true)->first();

        if (is_null($fromPort)) {
            return response()->json([
                'message' => 'This is not a recognized port.',
            ], 422);
        }

        if ($character->gold < $request->cost) {
            return response()->json([
                'message' => 'Not enough gold.',
            ], 422);
        }

        if (!$this->portService->doesMatch($character, $fromPort, $location, (int) $request->time_out_value, (int) $request->cost)) {
            return response()->json([
                'message' => 'Invalid input. Please refresh and try again.',
            ], 422);
        }

        $character->update([
            'can_move'          => false,
            'gold'              => $character->gold - $request->cost,
            'can_move_again_at' => now()->addMinutes($request->time_out_value),
        ]);

        $this->portService->setSail($character, $location);
        
        event(new MoveTimeOutEvent($character, $request->time_out_value));

        return response()->json([
            'character_position_details' => $character->map,
            'port_details'               => $this->portService->getPortDetails($character, $location),
        ]);
    }

    public function isWater(Request $request, Character $character) {

        $hasItem = $character->inventory->questItemSlots->filter(function($slot) {
            return $slot->item->effect === 'walk-on-water';
        })->isNotEmpty();
        
        $contents            = Storage::disk('maps')->get($character->map->gameMap->path);
        $this->imageResource = imagecreatefromstring($contents);

        $waterRgb = 112219255;
        $rgb      = imagecolorat($this->imageResource, $request->character_position_x, $request->character_position_y);

        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        $color = $r.$g.$b;

        if ((int) $color === $waterRgb && !$hasItem) {
            // TODO: Implement loic to check for a relic called: 'flask of fresh air'.
            return response()->json([], 422);
        }

        return response()->json([], 200);
    }
}
