<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;

class FactionLoyaltyAutomationWarningService
{
    /**
     * @param FactionLoyaltyService $factionLoyaltyService
     */
    public function __construct(private readonly FactionLoyaltyService $factionLoyaltyService) {}

    /**
     * @param Character $character
     * @return void
     */
    public function dismissLatestWarning(Character $character): void
    {
        $this->factionLoyaltyService->dismissLatestWarningNotice($character);
    }
}
