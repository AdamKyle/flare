<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CapitalCityUnitRequestMovement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $capitalCityQueueId, private readonly int $characterId) {}

    public function handle(CapitalCityUnitManagement $capitalCityUnitManagement): void
    {
        $queueData = CapitalCityUnitQueue::find($this->capitalCityQueueId);

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
                CapitalCityUnitRequestMovement::dispatch(
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

        event(new UpdateCapitalCityBuildingQueueTable($queueData->character));

        Log::channel('capital_city_unit_recruitments')->info('Processing Unit request', [
            '$queueData' => $queueData,
        ]);

        $capitalCityUnitManagement->processUnitRequest(
            $queueData
        );

    }
}
