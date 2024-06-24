<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CapitalCityBuildingRequestMovement implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $capitalCityQueueId
     * @param int $characterId
     */
    public function __construct(private readonly int $capitalCityQueueId, private readonly int $characterId) {}

    /**
     * @return void
     */
    public function handle(CapitalCityBuildingManagement $capitalCityBuildingManagement): void {
        $queueData = CapitalCityBuildingQueue::find($this->capitalCityQueueId);

        if (is_null($queueData)) {
            return;
        }

        if (!$queueData->completed_at->lessThanOrEqualTo(now())) {
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
                    $this->characterId,
                )->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $queueData->update([
            'status' => CapitalCityQueueStatus::PROCESSING,
        ]);

        $queueData = $queueData->refresh();

        $capitalCityBuildingManagement->processBuildingRequest(
            $queueData
        );

    }
}
