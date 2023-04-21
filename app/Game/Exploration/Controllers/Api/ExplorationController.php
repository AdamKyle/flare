<?php

namespace App\Game\Exploration\Controllers\Api;

use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AttackTypeValue;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationStatus;
use App\Game\Exploration\Services\AttackAutomationService;
use App\Flare\Values\AutomationType;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Exploration\Events\UpdateAutomationsList;
use App\Game\Exploration\Requests\ExplorationRequest;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Automation\Request\AttackAutomationStartRequest;
use Illuminate\Http\JsonResponse;

class ExplorationController extends Controller {

    /**
     * @var ExplorationAutomationService
     */
    private  ExplorationAutomationService $explorationAutomationService;

    /**
     * @param ExplorationAutomationService $explorationAutomationService
     */
    public function __construct(ExplorationAutomationService $explorationAutomationService) {
        $this->explorationAutomationService = $explorationAutomationService;
    }

    /**
     * @param ExplorationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function begin(ExplorationRequest $request, Character $character): JsonResponse {

        if (!AttackTypeValue::attackTypeExists($request->attack_type)) {
            return response()->json([
                'message' => 'Invalid attack type was selected. Please select from the drop down.'
            ], 422);
        }

        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING)->count() > 0) {
            return response()->json([
                'message' => 'Nope. You already have one in progress.'
            ], 422);
        }

        $location = Location::where('x', $character->map->character_position_x)
                            ->where('y', $character->map->character_position_y)
                            ->where('game_map_id', $character->map->game_map_id)
                            ->where('type', LocationType::UNDERWATER_CAVES)
                            ->first();

        if (!is_null($location)) {
            return response()->json([
                'message' => 'Nope. You cannot explore here.'
            ], 422);
        }

        $this->explorationAutomationService->beginAutomation($character, $request->all());

        return response()->json([
            'message' => 'Exploration has started. Check the exploration tab (beside server messages) for update. The tab will every five minutes, rewards are handed to you or disenchanted automatically.'
        ]);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function stop(Character $character): JsonResponse {

        $characterAutomation = CharacterAutomation::where('character_id', $character->id)->where('type', AutomationType::EXPLORING)->first();

        if (is_null($characterAutomation)) {
            return response()->json([
                'message' => 'Nope. You don\'t own that.'
            ], 422);
        }

        $characterAutomation->delete();

        $character = $character->refresh();

        event(new ExplorationTimeOut($character->user, 0));
        event(new ExplorationStatus($character->user, false));
        event(new UpdateCharacterStatus($character));

        event(new ExplorationLogUpdate($character->user, 'Exploration has been stopped at player request.'));

        return response()->json();
    }
}
