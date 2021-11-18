<?php

namespace App\Game\Automation\Controllers\Api;

use App\Game\Automation\Services\AttackAutomationService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Automation\Request\AttackAutomationStartRequest;

class AttackAutomationController extends Controller {

    public function begin(AttackAutomationStartRequest $request, Character $character, AttackAutomationService $attackAutomationService) {
        $response = $attackAutomationService->beginAutomation($character, $request->all());

        return response()->json([
            'message' => $response['message']
        ], $response['status']);
    }

    public function stop(Character $character) {

    }
}
