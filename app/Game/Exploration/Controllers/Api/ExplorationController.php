<?php

namespace App\Game\Exploration\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use App\Game\Exploration\Requests\ExplorationRequest;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ExplorationController extends Controller
{
    private ExplorationAutomationService $explorationAutomationService;

    public function __construct(ExplorationAutomationService $explorationAutomationService)
    {
        $this->explorationAutomationService = $explorationAutomationService;
    }

    public function begin(ExplorationRequest $request, Character $character): JsonResponse
    {

        if (! AttackTypeValue::attackTypeExists($request->attack_type)) {
            return response()->json([
                'message' => 'Invalid attack type was selected. Please select from the drop down.',
            ], 422);
        }

        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING)->count() > 0) {
            return response()->json([
                'message' => 'Nope. You already have one in progress.',
            ], 422);
        }

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->where('type', LocationType::UNDERWATER_CAVES)
            ->first();

        if (! is_null($location)) {
            return response()->json([
                'message' => 'Nope. You cannot explore here.',
            ], 422);
        }

        $this->explorationAutomationService->beginAutomation($character, $request->all());

        $timeDelay = $this->explorationAutomationService->getTimeDelay();

        return response()->json([
            'message' => 'Exploration has started. Check the exploration tab (beside server messages) for update. The tab will every '.$timeDelay.' minutes, rewards are handed to you or disenchanted automatically.',
        ]);
    }

    public function stop(Character $character): JsonResponse
    {

        $this->explorationAutomationService->stopExploration($character);

        return response()->json();
    }
}
