<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Support\Facades\Log;

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

        CapitalCityResourceRequest::query()
            ->where('completed_at', '<=', now())
            ->get()
            ->each(function (CapitalCityResourceRequest $resourceRequest): void {
                Log::warning('Deleted stale capital city resource request.', [
                    'resource_request_id' => $resourceRequest->id,
                ]);

                $resourceRequest->delete();
            });
    }
}
