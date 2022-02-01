<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\CharacterAutomation;
use App\Game\Automation\Events\AutomatedAttackStatus;
use App\Game\Automation\Services\AttackAutomationService;
use App\Game\Automation\Values\AutomationType;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Automation\Request\AttackAutomationStartRequest;

class AttackAutomationController extends Controller {

    public function index(Character $character, AttackAutomationService $attackAutomationService) {
        $automation = $character->currentAutomations()->where('type', AutomationType::ATTACK)->first();

        $data = $attackAutomationService->fetchData($character, $automation);

        return response()->json([
            'automation' => $data,
        ], 200);
    }

    public function begin(AttackAutomationStartRequest $request, Character $character, AttackAutomationService $attackAutomationService) {

        if (!$character->user->can_auto_battle) {
            return response()->json([
                'message' => 'You are not allowed to auto battle.'
            ], 422);
        }

        $response = $attackAutomationService->beginAutomation($character, $request->all());

        return response()->json([
            'message' => $response['message'],
            'id'      => $character->refresh()->currentAutomations()->where('type', AutomationType::ATTACK)->first()->id,
        ], $response['status']);
    }

    public function stop(CharacterAutomation $characterAutomation, Character $character) {

        if ($character->id !== $characterAutomation->character_id) {
            return response()->json([
                'message' => 'Nope. You don\'t own that.'
            ], 422);
        }

        $characterAutomation->delete();

        event(new UpdateTopBarEvent($character->refresh()));

        return response()->json([
            'message' => 'Attack Automation is stopping. Please wait for the timer to finish.'
        ]);
    }
}
