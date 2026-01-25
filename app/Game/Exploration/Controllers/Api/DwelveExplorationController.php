<?php

namespace App\Game\Exploration\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Flare\Values\LocationType;
use App\Game\Exploration\Requests\ExplorationRequest;
use App\Game\Exploration\Services\DwelveExplorationAutomationService;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DwelveExplorationController extends Controller
{

    public function __construct(private readonly DwelveExplorationAutomationService $dwelveExplorationAutomationService) {}

    public function begin(ExplorationRequest $request, Character $character): JsonResponse
    {

        if (! AttackTypeValue::attackTypeExists($request->attack_type)) {
            return response()->json([
                'message' => 'Invalid attack type was selected. Please select from the drop down.',
            ], 422);
        }

        if ($character->currentAutomations()->where('type', AutomationType::DWELVE)->count() > 0) {
            return response()->json([
                'message' => 'Nope. You already have one in progress.',
            ], 422);
        }

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->whereIn('type',  LocationType::CAVE_OF_MEMORIES)
            ->first();

        if ( is_null($location)) {
            return response()->json([
                'message' => 'You may only dwelve in locations that allow such an action child.',
            ], 422);
        }

        $this->dwelveExplorationAutomationService->beginAutomation($character, $request->all());

        return response()->json([
            'message' => 'Dwelve has started child. Let us see how long you last shall we? (Max dwelve time is 8 hours.)',
        ]);
    }

    public function stop(Character $character): JsonResponse
    {

        $this->dwelveExplorationAutomationService->stopExploration($character);

        return response()->json();
    }
}
