<?php

namespace App\Game\Exploration\Controllers\Api;

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

class ExplorationController extends Controller {

    public function begin(ExplorationRequest $request, Character $character, ExplorationAutomationService $explorationAutomationService) {

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

        $explorationAutomationService->beginAutomation($character, $request->all());


        return response()->json([
            'message' => 'Exploration has started. Check the exploration tab (beside server messages) for update. The tab will every five minutes, rewards are handed to you or disenchanted automatically.'
        ]);
    }

    public function stop(Character $character) {

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
