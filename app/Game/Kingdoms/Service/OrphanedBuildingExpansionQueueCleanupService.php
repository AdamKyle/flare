<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\BuildingExpansionQueue;

class OrphanedBuildingExpansionQueueCleanupService
{
    public function clean(): void
    {
        BuildingExpansionQueue::query()
            ->whereDoesntHave('building')
            ->delete();
    }
}
