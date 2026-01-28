<?php

namespace App\Game\Exploration\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use App\Game\Exploration\Requests\DelveExplorationRequest;
use App\Game\Exploration\Requests\ExplorationRequest;
use App\Game\Exploration\Services\DelveExplorationAutomationService;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DelveExplorationController extends Controller
{

    public function __construct(private readonly DelveExplorationAutomationService $delveExplorationAutomationService) {}

    public function begin(DelveExplorationRequest $request, Character $character): JsonResponse
    {

        if (! AttackTypeValue::attackTypeExists($request->attack_type)) {
            return response()->json([
                'message' => 'Invalid attack type was selected. Please select from the drop down.',
            ], 422);
        }

        if ($character->currentAutomations()->where('type', AutomationType::DELVE)->orWhere('type', AutomationType::EXPLORING)->count() > 0) {
            return response()->json([
                'message' => 'Nope. You already have one in progress.',
            ], 422);
        }

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->where('type', LocationType::CAVE_OF_MEMORIES)
            ->first();

        if ( is_null($location)) {
            return response()->json([
                'message' => 'You may only delve in locations that allow such an action child.',
            ], 422);
        }

        $this->delveExplorationAutomationService->beginAutomation($character, $request->all());

        return response()->json([
            'message' => 'Delve has started child. Let us see how long you last shall we? (Max delve time is 8 hours.)',
        ]);
    }

    public function stop(Character $character): JsonResponse
    {

        $this->delveExplorationAutomationService->stopExploration($character);

        return response()->json();
    }
}
