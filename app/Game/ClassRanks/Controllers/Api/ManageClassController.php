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
    public function __construct(
        private readonly ManageClassService $manageClassService,
        private readonly AutomationRestrictionService $automationRestrictionService,
    ) {}

    /**
     * Switch the character to the requested Class.
     */
    public function switchClass(Character $character, GameClass $gameClass): JsonResponse
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

    /**
     * Build the blocked-automation JSON response for the character, or null when not restricted.
     */
    private function automationRestrictionJsonResponse(Character $character): ?JsonResponse
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::CLASS_RANKS);

        if (is_null($restriction)) {
            return null;
        }

        return response()->json(['message' => $restriction['message']], 422);
    }
}
