<?php

namespace App\Game\Maps\Controllers\Api;

use App\Flare\Models\Npc;
use App\Game\Automation\Values\AutomationType;
use App\Game\Maps\Requests\TraverseRequest;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Http\Controllers\Controller;
use App\Flare\Models\User;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\MovementService;
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
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $xPosition    = $request->character_position_x;
        $yPosition    = $request->character_position_y;

        $location = Location::where('x', $xPosition)
                            ->where('y', $yPosition)
                            ->where('game_map_id', $character->map->game_map_id)
                            ->first();

        if (!is_null($location)) {
            if (!is_null($location->enemy_strength_type) && $character->currentAutomations()->where('type', AutomationType::ATTACK)->get()->isNotEmpty()) {
                event(new ServerMessageEvent($character->user, 'No. You are currently auto battling and the monsters here are different. Stop auto battling, then enter, then begin again.'));
                return response()->json(['message' => 'You\'re too busy.'], 422);
            }
        }

        // Are we at a special location, trying to leave?
        $location = Location::where('x', $character->x_position)
            ->where('y', $character->y_position)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (!is_null($location)) {
            if (!is_null($location->enemy_strength_type) && $character->currentAutomations()->where('type', AutomationType::ATTACK)->get()->isNotEmpty()) {
                event(new ServerMessageEvent($character->user, 'No. You are currently auto battling and the monsters here are different. Stop auto battling, then enter, then begin again.'));
                return response()->json(['message' => 'You\'re too busy.'], 422);
            }
        }

        $response = $movementSevice->updateCharacterPosition($character, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function traverse(TraverseRequest $request, Character $character, MovementService $movementService) {
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $response = $movementService->updateCharacterPlane($request->map_id, $character);

        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function teleport(TeleportRequest $request, Character $character, MovementService $movementSevice) {
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $response = $movementSevice->teleport($character, $request->x, $request->y, $request->cost, $request->timeout);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function setSail(SetSailValidation $request, Location $location, Character $character, MovementService $movementService) {
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $response = $movementService->setSail($character, $location, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function fetchQuests(Character $character) {
        $gameMap = $character->map->gameMap;
        $npcs    = Npc::where('game_map_id', $gameMap->id)->whereHas('quests')->with(
            'quests.childQuests',
            'quests.rewardItem',
            'quests.item',
            'quests.npc',
            'quests.npc.commands'
        )->get();

        return response()->json([
            'npcs'             => $npcs,
            'completed_quests' => $character->questsCompleted,
        ]);
    }
}
