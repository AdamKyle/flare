<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CapitalCityBuildingRequestCancellationMovement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $capitalCityCancellationQueueId,
        private readonly int $capitalCityQueueId,
        private readonly int $characterId,
        private readonly array $dataForCancellation
    ) {}

    /**
     * Handle the job.
     *
     * @throws Exception
     */
    public function handle(
        CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        KingdomBuildingService $kingdomBuildingService
    ): void {
        $queueData = CapitalCityBuildingQueue::find($this->capitalCityQueueId);

        if (is_null($queueData)) {
            CapitalCityBuildingCancellation::where('id', $this->capitalCityCancellationQueueId)->update(['status' => CapitalCityQueueStatus::CANCELLATION_REJECTED]);

            return;
        }

        if ($this->shouldDelayCancellation($queueData)) {
            return;
        }

        CapitalCityBuildingCancellation::where('id', $this->capitalCityCancellationQueueId)->update(['status' => CapitalCityQueueStatus::PROCESSING]);

        event(new UpdateCapitalCityBuildingQueueTable($queueData->character));

        $responseData = $this->processCancellations($queueData, $kingdomBuildingService);

        if (empty($responseData)) {
            return;
        }

        $this->updateQueueData($queueData, $responseData);

        event(new UpdateCapitalCityBuildingQueueTable($queueData->character));
        $capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($queueData);

        if (! collect($responseData)->contains(fn($response) => $response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED)) {
            $this->cleanupCancellationRecords();
        }
    }

    /**
     * Determine if the cancellation should be delayed.
     */
    private function shouldDelayCancellation(CapitalCityBuildingQueue $queueData): bool
    {
        if (! $queueData->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queueData->completed_at->diffInMinutes(now());

            if ($timeLeft >= 1) {
                $time = now()->addMinutes($timeLeft <= 15 ? $timeLeft : 15);

                // @codeCoverageIgnoreStart
                CapitalCityBuildingRequestCancellationMovement::dispatch(
                    $this->capitalCityCancellationQueueId,
                    $this->capitalCityQueueId,
                    $this->characterId,
                    $this->dataForCancellation
                )->onConnection('long_running')->onQueue('default_long')->delay($time);

                return true;
                // @codeCoverageIgnoreEnd
            }
        }

        return false;
    }

    /**
     * Process the cancellations for the buildings in the queue.
     *
     * @throws Exception
     */
    private function processCancellations(CapitalCityBuildingQueue $queueData, KingdomBuildingService $kingdomBuildingService): array
    {
        $character = $queueData->character;
        $queueDataMessages = $queueData->messages;

        return collect($this->dataForCancellation['building_ids'])->map(function ($buildingId) use ($kingdomBuildingService, $queueData, $character, $queueDataMessages) {
            $buildingInQueue = BuildingInQueue::where('kingdom_id', $queueData->kingdom_id)
                ->where('character_id', $this->characterId)
                ->where('building_id', $buildingId)
                ->first();

            $building = KingdomBuilding::find($buildingId);

            if (is_null($buildingInQueue) || $buildingInQueue->completed_at->lessThanOrEqualTo(now())) {

                CapitalCityBuildingCancellation::where('id', $this->capitalCityCancellationQueueId)->update(['status' => CapitalCityQueueStatus::CANCELLATION_REJECTED]);

                event(new UpdateCapitalCityBuildingQueueTable($character));

                $buildingName = is_null($building) ? 'Unknown Building' : $building->name;

                $queueDataMessages[] = 'Failed to cancel the request for building: ' . $buildingName . '. The building finished before the cancellation could arrive.';

                $queueData->update(['messages' => $queueDataMessages]);

                return [
                    'building_id' => $buildingId,
                    'status' => CapitalCityQueueStatus::CANCELLATION_REJECTED,
                ];
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
     */
    private function updateQueueData(CapitalCityBuildingQueue $queueData, array $responseData): void
    {
        $responseLookup = collect($responseData)
            ->pluck('status', 'building_id')
            ->toArray();

        $buildingRequestData = collect($queueData->building_request_data)->map(function ($request) use ($responseLookup) {
            if (isset($responseLookup[$request['building_id']])) {
                $request['secondary_status'] = $responseLookup[$request['building_id']];
            }

            return $request;
        })->toArray();

        $queueData->update(['building_request_data' => $buildingRequestData]);
        $queueData->refresh();

        if (collect($responseData)->contains(fn($response) => $response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED)) {
            $messages = $queueData->messages ?? [];
            $messages[] = 'Cancellation request for one of your buildings was rejected (See the building that states Cancellation Rejected) because it was too close to being done. No need to waste resources child!';

            $queueData->update(['messages' => $messages]);
        }
    }

    /**
     * Cleanup cancellation records based on the response data.
     */
    private function cleanupCancellationRecords(): void
    {
        $capitalCityBuildingCancellationQueue = CapitalCityBuildingCancellation::where('id', $this->capitalCityCancellationQueueId)->first();

        if (is_null($capitalCityBuildingCancellationQueue)) {
            return;
        }

        $character = $capitalCityBuildingCancellationQueue->character;

        $capitalCityBuildingCancellationQueue->delete();

        event(new UpdateCapitalCityBuildingQueueTable($character));
    }
}
