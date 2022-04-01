<?php

namespace App\Game\Exploration\Controllers\Api;

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

    public function index(Character $character, ExplorationAutomationService $explorationAutomationService) {
        $automation = $character->currentAutomations()->where('type', AutomationType::EXPLORING)->first();

        $data = $explorationAutomationService->fetchData($character, $automation);

        return response()->json([
            'automation' => $data,
        ], 200);
    }

    public function begin(ExplorationRequest $request, Character $character, ExplorationAutomationService $explorationAutomationService) {

        if (!AttackTypeValue::attackTypeExists($request->attack_type)) {
            return response()->json([
                'message' => 'invalid attack type was selected. Please select from the drop down.'
            ], 422);
        }

        if ($character->currentAutomations()->where('type', AutomationType::EXPLORING)->count() > 0) {
            return response()->json([
                'message' => 'Nope. You already have on in progress.'
            ], 422);
        }

        $response = $explorationAutomationService->beginAutomation($character, $request->all());

        return response()->json([
            'message' => $response['message'],
            'id'      => $character->refresh()->currentAutomations()->where('type', AutomationType::EXPLORING)->first()->id,
        ], $response['status']);
    }

    public function stop(CharacterAutomation $characterAutomation, Character $character) {

        if ($character->id !== $characterAutomation->character_id) {
            return response()->json([
                'message' => 'Nope. You don\'t own that.'
            ], 422);
        }

        $characterAutomation->delete();

        $character = $character->refresh();

        event(new ExplorationTimeOut($character->user, 0));
        event(new ExplorationStatus($character->user, false));
        event(new UpdateTopBarEvent($character));
        event(new UpdateAutomationsList($character->user, $character->currentAutomations));

        event(new ExplorationLogUpdate($character->user, 'Exploration has ended.'));

        return response()->json([
            'message' => 'Exploration has stopped.'
        ]);
    }
}
