<?php

namespace App\Game\ClassRanks\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\ClassRanks\Services\ManageClassService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ManageClassController extends Controller
{
    private ManageClassService $manageClassService;

    public function __construct(ManageClassService $manageClassService, private readonly AutomationRestrictionService $automationRestrictionService)
    {
        $this->manageClassService = $manageClassService;
    }

    public function switchClass(Character $character, GameClass $gameClass)
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $response = $this->manageClassService->switchClass($character, $gameClass);

        $status = $response['status'];
        unset($response['status']);

        return response()->json($response, $status);
    }

    private function automationRestrictionJsonResponse(Character $character): ?JsonResponse
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::CLASS_RANKS);

        if (is_null($restriction)) {
            return null;
        }

        return response()->json(['message' => $restriction['message']], 422);
    }
}
