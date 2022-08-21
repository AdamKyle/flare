<?php

namespace App\Game\Maps\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use App\Flare\Models\Quest;
use App\Flare\Models\Character;
use App\Game\Maps\Requests\TraverseRequest;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Requests\MoveRequest;
use App\Game\Maps\Requests\SetSailValidation;
use App\Game\Maps\Requests\TeleportRequest;
use App\Game\Maps\Requests\QuestDataRequest;
use App\Game\Maps\Services\SetSailService;
use App\Game\Maps\Services\TeleportService;
use App\Game\Maps\Services\WalkingService;

class MapController extends Controller {

    /**
     * @var MovementService $movementService
     */
    private MovementService $movementService;

    /**
     * @var SetSailService $setSail
     */
    private SetSailService $setSail;

    /**
     * @var TeleportService $teleportService
     */
    private TeleportService $teleportService;

    /**
     * @var WalkingService $walkingService
     */
    private WalkingService $walkingService;

    /**
     *
     * @param MovementService $movementService
     * @param TeleportService $teleportService
     * @param WalkingService $walkingService
     * @param SetSailService $setSail
     */
    public function __construct(MovementService $movementService,
                                TeleportService $teleportService,
                                WalkingService $walkingService,
                                SetSailService $setSail)
    {
        $this->movementService = $movementService;
        $this->teleportService = $teleportService;
        $this->walkingService  = $walkingService;
        $this->setSail         = $setSail;

        $this->middleware('is.character.dead')->except(['mapInformation', 'fetchQuests']);
    }

    /**
     * @param Character $character
     * @param LocationService $locationService
     * @return JsonResponse
     */
    public function mapInformation(Character $character, LocationService $locationService): JsonResponse {
        return response()->json($locationService->getLocationData($character));
    }

    /**
     * @param MoveRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function move(MoveRequest $request, Character $character): JsonResponse {
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

    /**
     * @return JsonResponse
     */
    public function traverseMaps(): JsonResponse {
        return response()->json($this->movementService->getMapsToTraverse(auth()->user()->character));
    }

    /**
     * @param TraverseRequest $request
     * @param Character $character
     * @param MovementService $movementService
     * @return JsonResponse
     */
    public function traverse(TraverseRequest $request, Character $character, MovementService $movementService): JsonResponse {
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $response = $movementService->updateCharacterPlane($request->map_id, $character);

        $status   = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param TeleportRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function teleport(TeleportRequest $request, Character $character): JsonResponse {
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

    /**
     * @param SetSailValidation $request
     * @param Character $character
     * @return JsonResponse
     */
    public function setSail(SetSailValidation $request, Character $character): JsonResponse {
        if (!$character->can_move) {
            return response()->json(['invalid input'], 429);
        }

        $response = $this->setSail->setCoordinatesToTravelTo($request->x, $request->y)
                                  ->setCost($request->cost)
                                  ->setTimeOutValue($request->timeout)
                                  ->setSail($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @param QuestDataRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchQuests(QuestDataRequest $request, Character $character): JsonResponse {
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
