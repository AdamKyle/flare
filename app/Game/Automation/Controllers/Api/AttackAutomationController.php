<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\CharacterAutomation;
use App\Game\Automation\Events\AutomatedAttackStatus;
use App\Game\Automation\Services\AttackAutomationService;
use App\Game\Automation\Values\AutomationType;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Automation\Request\AttackAutomationStartRequest;

class AttackAutomationController extends Controller {

    public function index(Character $character) {
        $automation = $character->currentAutomations()->where('type', AutomationType::ATTACK)->first();

        $skillCurrentlyTraining = $character->skills->filter(function ($skill) {
            return $skill->currently_training;
        })->first();

        $data = [];

        if (!is_null($automation)) {
            $data = [
                'id'                       => $automation->id,
                'skill_id'                 => !is_null($skillCurrentlyTraining) ? $skillCurrentlyTraining->id : null,
                'xp_towards'               => !is_null($skillCurrentlyTraining) ? $skillCurrentlyTraining->xp_towards : null,
                'auto_attack_length'       => $automation->completed_at->diffInHours($automation->started_at),
                'move_down_the_list_every' => $automation->move_down_monster_list_every,
                'selected_monster_id'      => $automation->monster_id,
                'attack_type'              => $automation->attack_type,
            ];

            event(new AutomatedAttackStatus($character->user, true));
        } else {
            event(new AutomatedAttackStatus($character->user, false));
        }


        return response()->json([
            'automation' => $data,
        ], 200);
    }

    public function begin(AttackAutomationStartRequest $request, Character $character, AttackAutomationService $attackAutomationService) {
        $response = $attackAutomationService->beginAutomation($character, $request->all());

        return response()->json([
            'message' => $response['message']
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
            'message' => 'Attack Automation Stopped.'
        ]);
    }
}
