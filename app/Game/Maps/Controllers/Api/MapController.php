<?php

namespace App\Game\Maps\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Models\Quest;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Automation\Values\AutomationType;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Requests\MoveRequest;
use App\Game\Maps\Requests\QuestDataRequest;
use App\Game\Maps\Requests\SetSailValidation;
use App\Game\Maps\Requests\TeleportRequest;
use App\Game\Maps\Requests\TraverseRequest;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\MovementService;
use App\Game\Maps\Services\PortService;
use App\Game\Maps\Services\SetSailService;
use App\Game\Maps\Services\TeleportService;
use App\Game\Maps\Services\WalkingService;
use App\Game\Maps\Transformers\GameMapDetailTransformer;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class MapController extends Controller
{
    public function __construct(
        private readonly MovementService $movementService,
        private readonly TeleportService $teleportService,
        private readonly WalkingService $walkingService,
        private readonly SetSailService $setSail,
        private readonly DistanceCalculation $distanceCalculation,
        private readonly LocationService $locationService,
        private readonly PortService $portService,
        private readonly GameMapDetailTransformer $gameMapDetailTransformer
    ) {
        $this->middleware('is.character.dead')->except(['mapInformation', 'fetchQuests']);
    }

    /**
     * Return the Player-safe factual detail representation for the given Game Map.
     */
    public function gameMapDetails(GameMap $gameMap): JsonResponse
    {
        $requiredItem = $gameMap->requiredItem();

        $detailData = [
            'game_map' => $gameMap,
            'required_item' => $requiredItem,
            'required_quest' => is_null($requiredItem) ? null : Quest::where('reward_item', $requiredItem->id)->first(),
            'required_location' => $gameMap->requiredLocation,
        ];

        return response()->json($this->gameMapDetailTransformer->transform($detailData), 200);
    }

    public function mapInformation(Character $character): JsonResponse
    {
        return response()->json($this->locationService->getMapData($character));
    }

    public function updateLocationActions(Character $character): JsonResponse
    {
        return response()->json($this->locationService->locationBasedEvents($character));
    }

    /**
     * @throws Exception
     */
    public function move(MoveRequest $request, Character $character): JsonResponse
    {
        if (! $character->can_move) {
            return response()->json(['invalid input'], 422);
        }

        if ($character->currentAutomations()
            ->where('character_id', $character->id)
            ->where('completed_at', '>', now())
            ->where(function ($query) {
                $query->where('type', AutomationType::DELVE->value);
            })
            ->exists()
        ) {
            return response()->json([
                'message' => 'You cannot escape the shadows child. Cancel the Delve if you want to wonder around in the wild.',
            ], 422);
        }

        $this->walkingService->setCoordinatesToTravelTo(
            $request->character_position_x,
            $request->character_position_y
        );

        $response = $this->walkingService->movePlayerToNewLocation($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function traverseMaps(): JsonResponse
    {
        return response()->json(array_values($this->movementService->getMapsToTraverse(auth()->user()->character)));
    }

    public function fetchTeleportCoordinates(Character $character): JsonResponse
    {
        return response()->json($this->locationService->getTeleportLocations($character));
    }

    public function fetchSetSailPorts(Character $character): JsonResponse
    {
        $portDetails = $this->portService->getPortDetails($character);

        if (is_null($portDetails)) {
            return response()->json(['message' => 'Invalid port location.'], 422);
        }

        return response()->json($portDetails);
    }

    public function getLocationInformation(Location $location): JsonResponse
    {
        return response()->json([
            'data' => $this->locationService->getLocationDetails($location),
        ]);
    }

    public function getLocationDroppableQuestItems(PaginationRequest $request, Location $location): JsonResponse
    {
        return response()->json(
            $this->locationService->getDroppableItems($location, $request->per_page, $request->page, $request->search_text)
        );
    }

    public function traverse(TraverseRequest $request, Character $character): JsonResponse
    {
        if (! $character->can_move) {
            return response()->json(['invalid input'], 422);
        }

        $response = $this->movementService->updateCharacterPlane($request->map_id, $character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    /**
     * @throws Exception
     */
    public function teleport(TeleportRequest $request, Character $character): JsonResponse
    {
        if (! $character->can_move) {
            return response()->json(['invalid input'], 422);
        }

        $distance = $this->distanceCalculation->calculatePixel(
            $request->x,
            $request->y,
            $character->map->character_position_x,
            $character->map->character_position_y
        );
        $timeout = $this->distanceCalculation->calculateMinutes($distance);

        $this->teleportService->setCoordinatesToTravelTo($request->x, $request->y)
            ->setCost($timeout * 1000)
            ->setTimeOutValue($timeout);

        $response = $this->teleportService
            ->teleport($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function setSail(SetSailValidation $request, Character $character): JsonResponse
    {
        if (! $character->can_move) {
            return response()->json(['invalid input'], 422);
        }

        $this->setSail->setCoordinatesToTravelTo($request->x, $request->y)
            ->setCost($request->cost)
            ->setTimeOutValue($request->timeout);

        $response = $this->setSail
            ->setSail($character);

        $status = $response['status'];

        unset($response['status']);

        return response()->json($response, $status);
    }

    public function fetchQuests(QuestDataRequest $request, Character $character): JsonResponse
    {
        if (! Cache::has('all-quests')) {
            Cache::put('all-quests', Quest::where('is_parent', true)->with('childQuests', 'factionMap', 'rewardItem', 'item', 'npc', 'npc.commands', 'npc.gameMap')->get());
        }

        $data = [
            'quests' => Cache::get('all-quests'),
            'completed_quests' => $character->questsCompleted()->pluck('quest_id'),
            'map_name' => $character->map->gameMap->name,
        ];

        $cacheToReset = Cache::get('character-quest-reset');
        $needsRefresh = false;

        if (! is_null($cacheToReset)) {
            $needsRefresh = in_array($character->id, $cacheToReset);
            $index = array_search($character->id, $cacheToReset);

            if ($index !== false) {
                unset($cacheToReset[$index]);
            }

            Cache::put('character-quest-reset', $cacheToReset);
        }

        if (! $request->completed_quests_only || $needsRefresh) {
            $data['all_quests'] = Cache::get('all-quests');
        }

        $data['was_reset'] = (! $request->completed_quests_only || $needsRefresh);

        return response()->json($data);
    }
}
