<?php

namespace App\Game\Maps\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Flare\Models\User;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\PortService;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Maps\Requests\IsWaterRequest;
use App\Game\Maps\Requests\MoveRequest;
use App\Game\Maps\Requests\SetSailValidation;
use App\Game\Maps\Requests\TeleportRequest;

class MapController extends Controller {

    /**
     * @var MapTileValue $mapTile
     */
    private $mapTile;

    /**
     * Constructor
     * 
     * @param PortService $portService
     * @param MapTileValue $mapTile
     */
    public function __construct(MapTileValue $mapTile) {
        $this->mapTile = $mapTile;

        $this->middleware('is.character.adventuring')->except(['mapInformation']);
        $this->middleware('is.character.dead')->except(['mapInformation']);
    }

    public function mapInformation(User $user, LocationService $locationService) {
        return response()->json($locationService->getLocationData($user->character), 200);
    }

    public function move(MoveRequest $request, Character $character, MovementService $movementSevice) {
        $character = $movementSevice->updateCharacterPosition($character, $request->all());

        $movementSevice->processArea($character);

        $movementSevice->updateCharacterMovementTimeOut($character);

        return response()->json([
            'port_details'      => $movementSevice->portDetails(),
            'adventure_details' => $movementSevice->adventureDetails(),
            'kingdom_details'   => $movementSevice->kingdomDetails(),
        ], 200);
    }

    public function teleport(TeleportRequest $request, Character $character, MovementService $movementSevice) {
        $response = $movementSevice->teleport($character, $request->x, $request->y, $request->cost, $request->timeout);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function setSail(SetSailValidation $request, Location $location, Character $character, MovementService $movementSevice) {
        $response = $movementSevice->setSail($character, $location, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function isWater(IsWaterRequest $request, Character $character) {
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