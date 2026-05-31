<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Requests\FactionLoyaltyAutomationWarningRequest;
use App\Game\Automation\Services\FactionLoyaltyAutomationWarningService;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyAutomationWarningController
{
    /**
     * @param FactionLoyaltyAutomationWarningService $factionLoyaltyAutomationWarningService
     */
    public function __construct(private readonly FactionLoyaltyAutomationWarningService $factionLoyaltyAutomationWarningService) {}

    /**
     * @param FactionLoyaltyAutomationWarningRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function dismiss(FactionLoyaltyAutomationWarningRequest $request, Character $character): JsonResponse
    {
        $this->factionLoyaltyAutomationWarningService->dismissLatestWarning($character);

        return response()->json();
    }
}
