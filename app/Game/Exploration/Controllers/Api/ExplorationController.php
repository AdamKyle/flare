<?php

namespace App\Game\Exploration\Controllers\Api;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\CharacterAutomation;
use App\Game\Automation\Events\AutomatedAttackStatus;
use App\Game\Automation\Services\AttackAutomationService;
use App\Game\Automation\Values\AutomationType;
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

    public function begin(AttackAutomationStartRequest $request, Character $character, ExplorationAutomationService $explorationAutomationService) {

        if (!$character->user->can_auto_battle) {
            return response()->json([
                'message' => 'You are not allowed to auto battle.'
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

        return response()->json([
            'message' => 'Attack Automation is stopping. Please wait for the timer to finish.'
        ]);
    }
}
