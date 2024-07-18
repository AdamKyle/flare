<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Service\UnitService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
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
     * @param CapitalCityUnitManagement $capitalCityUnitManagement
     * @param UnitService $unitService
     * @return void
     * @throws Exception
     */
    public function handle(
        CapitalCityUnitManagement $capitalCityUnitManagement,
        UnitService $unitService
    ): void {
        $queueData = CapitalCityUnitQueue::find($this->capitalCityQueueId);

        if (is_null($queueData)) {

            $cancellationQueue = CapitalCityUnitCancellation::where('id', $this->capitalCityCancellationQueueId)->first();

            $character = $cancellationQueue->character;

            $cancellationQueue->delete();

            event(new UpdateCapitalCityUnitQueueTable($character));

            return;
        }

        if ($this->shouldDelayCancellation($queueData)) {
            return;
        }

        CapitalCityUnitCancellation::where('id', $this->capitalCityCancellationQueueId)->update(['status' => CapitalCityQueueStatus::PROCESSING]);

        event(new UpdateCapitalCityUnitQueueTable($queueData->character));

        $responseData = $this->processCancellations($queueData, $unitService);

        if (empty($responseData)) {

            return;
        }

        $this->updateQueueData($queueData, $responseData);

        $capitalCityUnitManagement->possiblyCreateKingdomLog($queueData);

        CapitalCityUnitCancellation::where('id', $this->capitalCityCancellationQueueId)->delete();

        event(new UpdateCapitalCityUnitQueueTable($queueData->character));
    }

    /**
     * Determine if the cancellation should be delayed.
     *
     * @param CapitalCityUnitQueue $queueData
     * @return bool
     */
    private function shouldDelayCancellation(CapitalCityUnitQueue $queueData): bool
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
     * @param CapitalCityUnitQueue $queueData
     * @param UnitService $unitService
     * @return array
     */
    private function processCancellations(CapitalCityUnitQueue $queueData, UnitService $unitService): array
    {

        $messages = $queueData->messages;

        return collect($this->dataForCancellation['unit_ids'])->map(function ($unitId) use ($unitService, $queueData, $messages) {
            $unitQueue = UnitInQueue::where('kingdom_id', $queueData->kingdom_id)
                ->where('character_id', $this->characterId)
                ->where('game_unit_id', $unitId)
                ->first();

            $gameUnit = GameUnit::find($unitId);

            if (is_null($unitQueue)) {

                CapitalCityUnitQueue::where('id', $this->capitalCityCancellationQueueId)->update(['status' => CapitalCityQueueStatus::CANCELLATION_REJECTED]);

                $messages[] = 'Failed to cancel unit recruitment. Seems it must already be done for unit: ' . $gameUnit->name;

                $queueData->update(['messages' => $messages]);

                event(new UpdateCapitalCityUnitQueueTable($queueData->character));

                return [];
            }

            $unitService->cancelRecruit($unitQueue);

            return [
                'unit_id' => $unitQueue->game_unit_id,
                'status' => CapitalCityQueueStatus::CANCELLED,
            ];
        })->toArray();
    }

    /**
     * Update the queue data with the cancellation statuses.
     *
     * @param CapitalCityUnitQueue $queueData
     * @param array $responseData
     * @return void
     */
    private function updateQueueData(CapitalCityUnitQueue $queueData, array $responseData): void
    {

        $responseLookup = collect($responseData)
            ->reject(fn($response) => $response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED)
            ->pluck('status', 'unit_id')
            ->toArray();

        $unitRequestData = collect($queueData->unit_request_data)->map(function ($request) use ($responseLookup) {
            $gameUnit = GameUnit::where('name', $request['unit_name'])->first();

            if (isset($responseLookup[$gameUnit->id])) {
                $request['secondary_status'] = CapitalCityQueueStatus::CANCELLED;
            }
            return $request;
        })->toArray();

        $queueData->update(['unit_request_data' => $unitRequestData]);
        $queueData->refresh();

        if (collect($responseData)->contains(fn($response) => $response['status'] === CapitalCityQueueStatus::CANCELLATION_REJECTED)) {
            $messages = $queueData->messages ?? [];
            $messages[] = "Cancellation request for one of your units was rejected (See the unit that states Cancellation Rejected) because it was too close to being done. No need to waste resources child!";

            $queueData->update(['messages' => $messages]);
        }
    }
}
