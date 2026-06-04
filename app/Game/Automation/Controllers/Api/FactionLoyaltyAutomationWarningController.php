<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Requests\FactionLoyaltyAutomationWarningRequest;
use App\Game\Automation\Services\FactionLoyaltyAutomationWarningService;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyAutomationWarningController
{
    public function __construct(private readonly FactionLoyaltyAutomationWarningService $factionLoyaltyAutomationWarningService) {}

    public function dismiss(FactionLoyaltyAutomationWarningRequest $request, Character $character): JsonResponse
    {
        $warningId = $request->has('warning_id') ? $request->integer('warning_id') : null;

        return response()->json($this->factionLoyaltyAutomationWarningService->dismissLatestWarning($character, $warningId));
    }
}
