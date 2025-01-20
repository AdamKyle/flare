<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CapitalCityBuildingRequestMovement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $capitalCityQueueId) {}

    public function handle(CapitalCityBuildingManagement $capitalCityBuildingManagement): void
    {
        $queueData = CapitalCityBuildingQueue::find($this->capitalCityQueueId);

        if (is_null($queueData)) {
            return;
        }

        if (! $queueData->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queueData->completed_at->diffInMinutes(now());

            if ($timeLeft >= 1) {
                if ($timeLeft <= 15) {
                    $time = now()->addMinutes($timeLeft);
                } else {
                    $time = now()->addMinutes(15);
                }

                // @codeCoverageIgnoreStart
                CapitalCityBuildingRequestMovement::dispatch(
                    $this->capitalCityQueueId,
                )->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $queueData->update([
            'status' => CapitalCityQueueStatus::PROCESSING,
        ]);

        $queueData = $queueData->refresh();

        event(new UpdateCapitalCityBuildingQueueTable($queueData->character));

        Log::channel('capital_city_building_upgrades')->info('Processing Building Request', [
            '$queueData' => $queueData,
        ]);

        $capitalCityBuildingManagement->processBuildingRequest(
            $queueData
        );
    }
}
