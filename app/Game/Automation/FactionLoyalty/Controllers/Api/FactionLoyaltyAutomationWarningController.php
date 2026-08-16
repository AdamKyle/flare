<?php

namespace App\Game\Automation\FactionLoyalty\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\FactionLoyalty\Requests\FactionLoyaltyAutomationWarningRequest;
use App\Game\Automation\FactionLoyalty\Services\FactionLoyaltyAutomationWarningService;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyAutomationWarningController
{
    /**
     * @param  FactionLoyaltyAutomationWarningService  $factionLoyaltyAutomationWarningService  The Faction Loyalty warning service.
     */
    public function __construct(private readonly FactionLoyaltyAutomationWarningService $factionLoyaltyAutomationWarningService) {}

    /**
     * Dismiss an active Faction Loyalty automation warning for the character.
     *
     * @param  FactionLoyaltyAutomationWarningRequest  $request  The dismiss request, optionally naming a warning id.
     * @param  Character  $character  The character dismissing the warning.
     * @return JsonResponse The updated warning state.
     */
    public function dismiss(FactionLoyaltyAutomationWarningRequest $request, Character $character): JsonResponse
    {
        $warningId = $request->has('warning_id') ? $request->integer('warning_id') : null;

        return response()->json($this->factionLoyaltyAutomationWarningService->dismissLatestWarning($character, $warningId));
    }
}
