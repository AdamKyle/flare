<?php

namespace App\Game\Kingdoms\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;


class CapitalCityBuildingRequestCancellationMovement implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $capitalCityCancellationQueueId
     * @param int $capitalCityQueueId
     * @param int $characterId
     * @param array $dataForCancellation
     */
    public function __construct(private readonly int $capitalCityCancellationQueueId,
                                private readonly int $capitalCityQueueId,
                                private readonly int $characterId,
                                private readonly array $dataForCancellation
    ) {}

    /**
     * @param CapitalCityBuildingManagement $capitalCityBuildingManagement
     * @param CapitalCityManagementService $capitalCityManagementService
     * @param KingdomBuildingService $kingdomBuildingService
     * @return void
     * @throws Exception
     */
    public function handle(CapitalCityBuildingManagement $capitalCityBuildingManagement, CapitalCityManagementService $capitalCityManagementService, KingdomBuildingService $kingdomBuildingService): void
    {
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
                CapitalCityBuildingRequestCancellationMovement::dispatch(
                    $this->capitalCityQueueId,
                    $this->characterId,
                )->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $responseData = collect($this->dataForCancellation['building_ids'])->map(function ($buildingId) use ($kingdomBuildingService, $queueData) {
            $buildingInQueue = BuildingInQueue::where('kingdom_id', $queueData->kingdom_id)
                ->where('character_id', $this->characterId)
                ->where('building_id', $buildingId)
                ->first();

            if (is_null($buildingInQueue)) {
                throw new Exception('No building queue data found for building: ' . $buildingId .
                    ' for kingdom ' .  $queueData->kingdom_id
                );
            }

            $result = $kingdomBuildingService->cancelKingdomBuildingUpgrade($buildingInQueue);

            return [
                'building_id' => $buildingInQueue->building_id,
                'status' => $result ? CapitalCityQueueStatus::CANCELLED : CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ];
        })->toArray();

        $responseLookup = collect($responseData)
            ->reject(function ($response) {
                return $response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED;
            })
            ->pluck('status', 'building_id')
            ->toArray();

        $buildingRequestData = collect($queueData->building_request_data)->map(function ($request) use ($responseLookup) {
            if (isset($responseLookup[$request['building_id']])) {
                $request['secondary_status'] = CapitalCityQueueStatus::CANCELLED;
            }
            return $request;
        })->toArray();

        $queueData->update([
            'building_request_data' => $buildingRequestData,
        ]);

        $queueData = $queueData->refresh();

        $hasRejections = collect($responseData)->contains(function ($response) {
            return $response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED;
        });

        if ($hasRejections) {
            $messages = $queueData->messages ?? [];
            $messages[] = "Cancellation request for one of your buildings was rejected (See the building that states Cancellation Rejected) because it was too close to being done. No need to waste resources child!";

            $queueData->update([
                'messages' => $messages,
            ]);
        }

        event(new UpdateCapitalCityBuildingQueueTable($queueData->character, $queueData->requestingKingdom));

        $capitalCityBuildingManagement->possiblyCreateLogForQueue($queueData);

    }
}
