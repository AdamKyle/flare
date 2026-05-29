<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\LocationType;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Requests\ExplorationRequest;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Automation\Services\ExplorationAutomationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ExplorationController extends Controller
{
    use ChecksAutomationRestrictions;

    private ExplorationAutomationService $explorationAutomationService;

    public function __construct(ExplorationAutomationService $explorationAutomationService)
    {
        $this->explorationAutomationService = $explorationAutomationService;
    }

    public function begin(ExplorationRequest $request, Character $character): JsonResponse
    {
        $params = $request->all();
        $params['attack_type'] = empty($params['attack_type']) ? AttackTypeValue::ATTACK : $params['attack_type'];

        if (! AttackTypeValue::attackTypeExists($params['attack_type'])) {
            return response()->json([
                'message' => 'Invalid attack type was selected. Please select from the drop down.',
            ], 422);
        }

        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::START_EXPLORATION);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->whereIn('type', [
                LocationType::UNDERWATER_CAVES,
                LocationType::ALCHEMY_CHURCH,
                LocationType::LORDS_STRONG_HOLD,
                LocationType::BROKEN_ANVIL,
                LocationType::TWSITED_MAIDENS_DUNGEONS,
            ])
            ->first();

        if (! is_null($location)) {
            return response()->json([
                'message' => 'This place is far too special for you to be able to explore. Manual fighting is only allowed here child.',
            ], 422);
        }

        $this->explorationAutomationService->beginAutomation($character, $params);

        $timeDelay = $this->explorationAutomationService->getTimeDelay();

        return response()->json([
            'message' => 'Exploration has started. Check the exploration tab (beside server messages) for update. The tab will every ' . $timeDelay . ' minutes, rewards are handed to you or disenchanted automatically.',
        ]);
    }

    public function stop(Character $character): JsonResponse
    {

        $this->explorationAutomationService->stopExploration($character);

        return response()->json();
    }
}
