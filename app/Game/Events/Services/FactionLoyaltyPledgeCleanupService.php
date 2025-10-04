<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Faction;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;

class FactionLoyaltyPledgeCleanupService
{
    public function __construct(private readonly FactionLoyaltyService $service) {}

    public function unpledgeIfOnFaction(Character $character, ?Faction $faction): void
    {
        if (is_null($faction)) {
            return;
        }

        $factionLoyalty = $character->factionLoyalties()
            ->where('faction_id', $faction->id)
            ->first();

        if (is_null($factionLoyalty)) {
            return;
        }

        $assistingNpc = $factionLoyalty
            ->factionLoyaltyNpcs()
            ->where('currently_helping', true)
            ->first();

        if (! is_null($assistingNpc)) {
            $this->service->stopAssistingNpc($character, $assistingNpc);
        }

        $this->service->removePledge($character, $faction);
    }
}
