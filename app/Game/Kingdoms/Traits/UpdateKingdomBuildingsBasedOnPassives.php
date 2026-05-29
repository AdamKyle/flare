<?php

namespace App\Game\Kingdoms\Traits;

use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\KingdomBuildingUnlockSyncService;

trait UpdateKingdomBuildingsBasedOnPassives
{
    /**
     * Update weather the buildings are locked or not.
     *
     * - Used for purchasing a kingdom.
     */
    public function updateBuildings(Kingdom $kingdom): Kingdom
    {
        return resolve(KingdomBuildingUnlockSyncService::class)->syncForKingdom($kingdom);
    }
}
