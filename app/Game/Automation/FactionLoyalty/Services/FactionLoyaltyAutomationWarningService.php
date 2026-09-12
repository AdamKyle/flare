<?php

namespace App\Game\Automation\FactionLoyalty\Services;

use App\Flare\Models\Character;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;

class FactionLoyaltyAutomationWarningService
{
    /**
     * @param FactionLoyaltyService $factionLoyaltyService The Faction Loyalty domain service.
     */
    public function __construct(private readonly FactionLoyaltyService $factionLoyaltyService) {}

    /**
     * Dismiss the character's latest Faction Loyalty automation warning, or a specific one by id.
     *
     * @param Character $character The character dismissing the warning.
     * @param int|null $warningId The specific warning id to dismiss, or null for the latest.
     * @return array The updated warning state.
     */
    public function dismissLatestWarning(Character $character, ?int $warningId = null): array
    {
        return $this->factionLoyaltyService->dismissLatestWarningNotice($character, $warningId);
    }
}
