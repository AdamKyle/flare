<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;

class FactionLoyaltyAutomationWarningService
{
    public function __construct(private readonly FactionLoyaltyService $factionLoyaltyService) {}

    public function dismissLatestWarning(Character $character, ?int $warningId = null): array
    {
        return $this->factionLoyaltyService->dismissLatestWarningNotice($character, $warningId);
    }
}
