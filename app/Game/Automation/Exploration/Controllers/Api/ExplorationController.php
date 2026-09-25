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
use App\Game\Monsters\Services\MonsterListService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ExplorationController extends Controller
{
    use ChecksAutomationRestrictions;

    /**
     * @param ExplorationAutomationService $explorationAutomationService
     * @param MonsterListService $monsterListService
     */
    public function __construct(
        private readonly ExplorationAutomationService $explorationAutomationService,
        private readonly MonsterListService $monsterListService,
    ) {}

    /**
     * Start Exploration automation for the character with the validated request options.
     *
     * @param ExplorationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function begin(ExplorationRequest $request, Character $character): JsonResponse
    {
        $params = $request->all();
        $params['attack_type'] = empty($params['attack_type']) ? AttackType::ATTACK->value : $params['attack_type'];
        $params['selected_monster_id'] = $request->integer('selected_monster_id');

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

        $selectedMonster = $this->monsterListService->getMonsterForFight($character, $params['selected_monster_id']);

        if (is_null($selectedMonster)) {
            return response()->json([
                'message' => 'That monster is not available to explore at your current location. Please select another monster.',
            ], 422);
        }

        $result = $this->explorationAutomationService->beginAutomation($character, $params);

        $status = $result['status'];
        unset($result['status']);

        if ($status !== 200) {
            return response()->json($result, $status);
        }

        $timeDelay = $this->explorationAutomationService->getTimeDelay();

        return response()->json([
            'message' => 'Exploration has started. Check the exploration tab (beside server messages) for update. The tab will every '.$timeDelay.' minutes, rewards are handed to you or disenchanted automatically.',
            'exploration_message' => $result['exploration_message'],
        ]);
    }

    /**
     * Stop the character's active Exploration automation.
     *
     * @param Character $character
     * @return JsonResponse
     */
    public function stop(Character $character): JsonResponse
    {
        $result = $this->explorationAutomationService->stopExploration($character);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
