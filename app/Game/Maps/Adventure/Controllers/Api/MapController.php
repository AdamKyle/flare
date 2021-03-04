<?php

namespace App\Game\Maps\Adventure\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\User;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Requests\SetSailValidation;
use App\Game\Maps\Adventure\Requests\TeleportValidation;
use App\Game\Maps\Adventure\Services\LocationService;
use App\Game\Maps\Adventure\Services\MovementService;
use App\Game\Maps\Adventure\Services\PortService;
use App\Game\Maps\Adventure\Values\MapTileValue;

class MapController extends Controller {

    private $portService;

    private $mapTile;

    public function __construct(PortService $portService, MapTileValue $mapTile) {

        $this->portService      = $portService;
        $this->mapTile          = $mapTile;

        $this->middleware('auth:api');
        $this->middleware('is.character.adventuring')->except(['index']);
        $this->middleware('is.character.dead')->except(['index']);
    }

    public function index(User $user, LocationService $locationService) {
         
        return response()->json($locationService->getLocationData($user), 200);
    }

    public function move(Request $request, Character $character, MovementService $service) {

        $character->map->update([
            'character_position_x' => $request->character_position_x,
            'character_position_y' => $request->character_position_y,
            'position_x'           => $request->position_x,
            'position_y'           => $request->position_y,
        ]);

        $service->processArea($request->character_position_x, $request->character_position_y, $character);
        
        $character->update([
            'can_move'          => false,
            'can_move_again_at' => now()->addSeconds(10),
        ]);

        event(new MoveTimeOutEvent($character));

        return response()->json([
            'port_details'      => $service->portDetails(),
            'adventure_details' => $service->adventureDetails(),
            'kingdom_details'   => $service->kingdomDetails(),
        ], 200);
    }

    public function setSail(SetSailValidation $request, Location $location, Character $character, MovementService $service ) {
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

        $service->giveQuestReward($location, $character);
        
        event(new MoveTimeOutEvent($character, $request->time_out_value, true));
        event(new UpdateTopBarEvent($character));

        return response()->json([
            'character_position_details' => $character->map,
            'port_details'               => $this->portService->getPortDetails($character, $location),
            'adventure_details'          => $location->adventures->isNotEmpty() ? $location->adventures : [],
        ]);
    }

    public function teleport(TeleportValidation $request, Character $character, MovementService $service) {
        $response = $service->teleport($character, $request->x, $request->y, $request->cost, $request->timeout);

        if ($response['status'] === 422) {
            return response()->json([
                'message' => $response['message']
            ], 422);
        }

        return response()->json([], 200);
    }

    public function isWater(Request $request, Character $character) {
        
        $color = $this->mapTile->getTileColor($character, $request->character_position_x, $request->character_position_y);

        if ($this->mapTile->isWaterTile((int) $color)) {
            $hasItem = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === 'walk-on-water';
            })->isNotEmpty();

            if (!$hasItem) {
                return response()->json([], 422);
            }
        }

        return response()->json([], 200);
    }
}
