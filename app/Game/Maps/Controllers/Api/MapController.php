<?php

namespace App\Game\Maps\Controllers\Api;

use App\Flare\Models\GameMap;
use App\Game\Maps\Requests\QuestDataRequest;
use App\Game\Maps\Services\TeleportService;
use App\Game\Maps\Services\WalkingService;
use Cache;
use App\Flare\Models\Npc;
use App\Flare\Models\Quest;
use App\Flare\Values\AutomationType;
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
    private MapTileValue $mapTile;

    /**
     * @var MovementService $movementService
     */
    private MovementService $movementService;

    /**
     * @var TeleportService $teleportService
     */
    private TeleportService $teleportService;

    /**
     * @var WalkingService $walkingService
     */
    private WalkingService $walkingService;

    /**
     * Constructor
     *
     * @param MapTileValue $mapTile
     * @param MovementService $movementService
     * @param TeleportService $teleportService
     * @param WalkingService $walkingService
     */
    public function __construct(MapTileValue $mapTile, MovementService $movementService, TeleportService $teleportService, WalkingService $walkingService) {
        $this->mapTile         = $mapTile;
        $this->movementService = $movementService;
        $this->teleportService = $teleportService;
        $this->walkingService  = $walkingService;

        $this->middleware('is.character.dead')->except(['mapInformation', 'fetchQuests']);
    }

    public function mapInformation(Character $character, LocationService $locationService) {
        return response()->json($locationService->getLocationData($character), 200);
    }

    public function move(MoveRequest $request, Character $character) {
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $response = $this->walkingService->setCoordinatesToTravelTo(
            $request->character_position_x,
            $request->character_position_y
        )->movePlayerToNewLocation($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function traverseMaps() {
        return response()->json($this->movementService->getMapsToTraverse(auth()->user()->character));
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

    public function teleport(TeleportRequest $request, Character $character) {
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $response = $this->teleportService->setCoordinatesToTravelTo($request->x, $request->y)
                                          ->setCost($request->cost)
                                          ->setTimeOutValue($request->timeout)
                                          ->teleport($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function setSail(SetSailValidation $request, Character $character, MovementService $movementService) {
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $response = $movementService->setSail($character, $request->all());

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function fetchQuests(QuestDataRequest $request, Character $character) {
        if (!Cache::has('all-quests')) {
            Cache::put('all-quests', Quest::where('is_parent', true)->with('childQuests', 'factionMap', 'rewardItem', 'item', 'npc', 'npc.commands', 'npc.gameMap')->get());
        }

        $data = [
            'quests'           => Cache::get('all-quests'),
            'completed_quests' => $character->questsCompleted()->pluck('quest_id'),
            'map_name'         => $character->map->gameMap->name,
        ];

        $cacheToReset = Cache::get('character-quest-reset');
        $needsRefresh = false;

        if (!is_null($cacheToReset)) {
            $needsRefresh = in_array($character->id, $cacheToReset);
            $index        = array_search($character->id, $cacheToReset);

            if ($index !== false) {
                unset($cacheToReset[$index]);
            }

            Cache::put('character-quest-reset', $cacheToReset);
        }

        if (!$request->completed_quests_only || $needsRefresh) {
            $data['all_quests'] = Cache::get('all-quests');
        }

        $data['was_reset'] = (!$request->completed_quests_only || $needsRefresh);

        return response()->json($data);
    }
}
