<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingCancellation;
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
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

class CapitalCityUnitRequestCancellationMovement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $capitalCityCancellationQueueId
     * @param int $capitalCityQueueId
     * @param int $characterId
     * @param array $dataForCancellation
     */
    public function __construct(
        private readonly int $capitalCityCancellationQueueId,
        private readonly int $capitalCityQueueId,
        private readonly int $characterId,
        private readonly array $dataForCancellation
    ) {}

    /**
     * Handle the job.
     *
     * @param CapitalCityBuildingManagement $capitalCityBuildingManagement
     * @param KingdomBuildingService $kingdomBuildingService
     * @return void
     * @throws Exception
     */
    public function handle(
        CapitalCityBuildingManagement $capitalCityBuildingManagement,
        KingdomBuildingService $kingdomBuildingService
    ): void {
        $queueData = CapitalCityBuildingQueue::find($this->capitalCityQueueId);

        if (is_null($queueData)) {

            CapitalCityBuildingQueue::where('id', $this->capitalCityCancellationQueueId)->update(['status' => CapitalCityQueueStatus::CANCELLATION_REJECTED]);

            event(new UpdateCapitalCityBuildingQueueTable($queueData->character, $queueData->requestedKingdom));

            return;
        }

        if ($this->shouldDelayCancellation($queueData)) {
            return;
        }

        CapitalCityBuildingQueue::where('id', $this->capitalCityQueueId)->update(['status' => CapitalCityQueueStatus::PROCESSING]);

        $responseData = $this->processCancellations($queueData, $kingdomBuildingService);
        $this->updateQueueData($queueData, $responseData);

        event(new UpdateCapitalCityBuildingQueueTable($queueData->character, $queueData->requestingKingdom));
        $capitalCityBuildingManagement->possiblyCreateLogForQueue($queueData);

        $this->cleanupCancellationRecords($responseData);
    }

    /**
     * Determine if the cancellation should be delayed.
     *
     * @param CapitalCityBuildingQueue $queueData
     * @return bool
     */
    private function shouldDelayCancellation(CapitalCityBuildingQueue $queueData): bool
    {
        if (!$queueData->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queueData->completed_at->diffInMinutes(now());

            if ($timeLeft >= 1) {
                $time = now()->addMinutes($timeLeft <= 15 ? $timeLeft : 15);

                // @codeCoverageIgnoreStart
                CapitalCityUnitRequestCancellationMovement::dispatch(
                    $this->capitalCityQueueId,
                    $this->characterId
                )->delay($time);

                return true;
                // @codeCoverageIgnoreEnd
            }
        }

        return false;
    }

    /**
     * Process the cancellations for the buildings in the queue.
     *
     * @param CapitalCityBuildingQueue $queueData
     * @param KingdomBuildingService $kingdomBuildingService
     * @return array
     * @throws Exception
     */
    private function processCancellations(CapitalCityBuildingQueue $queueData, KingdomBuildingService $kingdomBuildingService): array
    {
        return collect($this->dataForCancellation['building_ids'])->map(function ($buildingId) use ($kingdomBuildingService, $queueData) {
            $buildingInQueue = BuildingInQueue::where('kingdom_id', $queueData->kingdom_id)
                ->where('character_id', $this->characterId)
                ->where('building_id', $buildingId)
                ->first();

            if (is_null($buildingInQueue)) {

                CapitalCityBuildingQueue::where('id', $this->capitalCityCancellationQueueId)->update(['status' => CapitalCityQueueStatus::CANCELLATION_REJECTED]);

                event(new UpdateCapitalCityBuildingQueueTable($queueData->character, $queueData->requestingKingdom));

                throw new Exception('No building queue data found for building: ' . $buildingId . ' for kingdom ' .  $queueData->kingdom_id);
            }

            $result = $kingdomBuildingService->cancelKingdomBuildingUpgrade($buildingInQueue);

            return [
                'building_id' => $buildingInQueue->building_id,
                'status' => $result ? CapitalCityQueueStatus::CANCELLED : CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ];
        })->toArray();
    }

    /**
     * Update the queue data with the cancellation statuses.
     *
     * @param CapitalCityBuildingQueue $queueData
     * @param array $responseData
     * @return void
     */
    private function updateQueueData(CapitalCityBuildingQueue $queueData, array $responseData): void
    {
        $responseLookup = collect($responseData)
            ->reject(fn($response) => $response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED)
            ->pluck('status', 'building_id')
            ->toArray();

        $buildingRequestData = collect($queueData->building_request_data)->map(function ($request) use ($responseLookup) {
            if (isset($responseLookup[$request['building_id']])) {
                $request['secondary_status'] = CapitalCityQueueStatus::CANCELLED;
            }
            return $request;
        })->toArray();

        $queueData->update(['building_request_data' => $buildingRequestData]);
        $queueData->refresh();

        if (collect($responseData)->contains(fn($response) => $response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED)) {
            $messages = $queueData->messages ?? [];
            $messages[] = "Cancellation request for one of your buildings was rejected (See the building that states Cancellation Rejected) because it was too close to being done. No need to waste resources child!";

            $queueData->update(['messages' => $messages]);
        }
    }

    /**
     * Cleanup cancellation records based on the response data.
     *
     * @param array $responseData
     * @return void
     */
    private function cleanupCancellationRecords(array $responseData): void
    {
        foreach ($responseData as $response) {
            if ($response['status'] === CapitalCityQueueStatus::CANCELLED) {
                CapitalCityBuildingCancellation::where('id', $this->capitalCityCancellationQueueId)->delete();
            } elseif ($response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED) {
                CapitalCityBuildingQueue::where('id', $this->capitalCityQueueId)->update(['status' => CapitalCityQueueStatus::CANCELLATION_REJECTED]);
            }
        }
    }
}
