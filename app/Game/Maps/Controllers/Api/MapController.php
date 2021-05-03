<?php

namespace App\Game\Maps\Controllers\Api;

use App\Game\Maps\Requests\TraverseRequest;
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
        $response = $movementSevice->updateCharacterPosition($character, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function traverse(TraverseRequest $request, Character $character, MovementService $movementService) {
        $response = $movementService->updateCharacterPlane($request->map_id, $character);

        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
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
}
