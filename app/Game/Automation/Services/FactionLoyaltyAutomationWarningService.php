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
     * @param int|null $warningId
     * @return array
     */
    public function dismissLatestWarning(Character $character, ?int $warningId = null): array
    {
        return $this->factionLoyaltyService->dismissLatestWarningNotice($character, $warningId);
    }
}
