<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

class CapitalCityQueueCleanupService
{
    private array $brokenStatuses = [
        CapitalCityQueueStatus::PROCESSING,
        CapitalCityQueueStatus::REQUESTING,
    ];

    public function clean(): void
    {
        CapitalCityBuildingQueue::query()
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', now())
            ->whereIn('status', $this->brokenStatuses)
            ->delete();

        CapitalCityUnitQueue::query()
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', now())
            ->whereIn('status', $this->brokenStatuses)
            ->delete();
    }
}
