<?php

namespace App\Game\Automation\Exploration\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Exploration\Requests\ExplorationRequest;
use App\Game\Automation\Exploration\Services\ExplorationAutomationService;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Maps\Values\LocationType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ExplorationController extends Controller
{
    use ChecksAutomationRestrictions;

    private ExplorationAutomationService $explorationAutomationService;

    /**
     * @param ExplorationAutomationService $explorationAutomationService The Exploration automation service.
     */
    public function __construct(ExplorationAutomationService $explorationAutomationService)
    {
        $this->explorationAutomationService = $explorationAutomationService;
    }

    /**
     * Start Exploration automation for the character with the validated request options.
     *
     * @param ExplorationRequest $request The validated Exploration start request.
     * @param Character $character The character starting Exploration.
     * @return JsonResponse The start confirmation or validation error response.
     */
    public function begin(ExplorationRequest $request, Character $character): JsonResponse
    {
        $params = $request->all();
        $params['attack_type'] = empty($params['attack_type']) ? AttackType::ATTACK->value : $params['attack_type'];

        if (! AttackType::attackTypeExists($params['attack_type'])) {
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
                LocationType::UNDERWATER_CAVES->value,
                LocationType::ALCHEMY_CHURCH->value,
                LocationType::LORDS_STRONG_HOLD->value,
                LocationType::BROKEN_ANVIL->value,
                LocationType::TWISTED_MAIDENS_DUNGEONS->value,
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
            'message' => 'Exploration has started. Check the exploration tab (beside server messages) for update. The tab will every '.$timeDelay.' minutes, rewards are handed to you or disenchanted automatically.',
        ]);
    }

    /**
     * Stop the character's active Exploration automation.
     *
     * @param Character $character The character stopping Exploration.
     * @return JsonResponse Empty confirmation response.
     */
    public function stop(Character $character): JsonResponse
    {

        $this->explorationAutomationService->stopExploration($character);

        return response()->json();
    }
}
