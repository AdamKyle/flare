<?php

namespace App\Game\Kingdoms\Service;

use App\Game\Core\Traits\ResponseBuilder;
use Carbon\Carbon;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestCancellationMovement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;

class CancelBuildingRequestService {

    use ResponseBuilder;

    /**
     * @param UnitMovementService $unitMovementService
     */
    public function __construct(private readonly UnitMovementService $unitMovementService) {}

    /**
     * Handle the building request cancellation.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return array
     */
    public function handleCancelRequest(Character $character, Kingdom $kingdom, array $requestData): array
    {
        $queue = $this->getBuildingQueue($character, $kingdom, $requestData['capital_city_building_queue_id']);

        if (is_null($queue)) {
            return $this->errorResult('What are you trying to cancel child?');
        }

        $deleteQueue = $requestData['delete_queue'];
        $buildingToDelete = $requestData['building_id'] ?? null;
        $time = $this->calculateTime($character, $queue);

        if ($queue->status === CapitalCityQueueStatus::TRAVELING) {
            return $this->handleTravelingQueue($queue, $deleteQueue, $buildingToDelete, $character, $kingdom);
        }

        if ($deleteQueue) {
            return $this->handleDeleteQueue($queue, $character, $kingdom, $time);
        }

        if (!is_null($buildingToDelete)) {
            return $this->handleSingleBuildingCancel($queue, $character, $kingdom, $buildingToDelete, $time);
        }

        return $this->errorResult('Something is wrong. Nothing was done.');
    }

    /**
     * Get the building queue.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param int $queueId
     * @return CapitalCityBuildingQueue|null
     */
    private function getBuildingQueue(Character $character, Kingdom $kingdom, int $queueId): ?CapitalCityBuildingQueue {
        return CapitalCityBuildingQueue::where('id', $queueId)
            ->where('character_id', $character->id)
            ->where('kingdom_id', $kingdom->id)
            ->first();
    }

    /**
     * Calculate the time required to travel to the kingdom.
     *
     * @param Character $character
     * @param CapitalCityBuildingQueue $queue
     * @return Carbon
     */
    private function calculateTime(Character $character, CapitalCityBuildingQueue $queue): Carbon {
        $time = $this->unitMovementService->determineTimeRequired(
            $character,
            $queue->kingdom,
            $queue->requested_kingdom,
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION
        );

        return now()->addMinutes($time);
    }

    /**
     * Handle deleting a queue or multiple queues.
     *
     * @param CapitalCityBuildingQueue $queue
     * @param bool $deleteQueue
     * @param int|null $buildingToDelete
     * @param Character $character
     * @param Kingdom $kingdom
     * @return array
     */
    private function handleTravelingQueue(CapitalCityBuildingQueue $queue, bool $deleteQueue, ?int $buildingToDelete, Character $character, Kingdom $kingdom): array
    {
        if ($deleteQueue) {
            $queue->delete();

            event(new UpdateCapitalCityBuildingQueueTable($character->refresh(), $kingdom));

            return $this->successResult(['message' => 'All orders have been canceled.']);
        }

        $this->updateBuildingRequestData($queue, $buildingToDelete);
        $deleted = $this->possiblyDeleteBuildingQueue($queue->refresh());
        event(new UpdateCapitalCityBuildingQueueTable($character->refresh(), $kingdom));

        $message = $deleted ? 'The last of your orders has been canceled.' : 'The selected building has been stricken from the request.';
        return $this->successResult(['message' => $message]);
    }

    /**
     * Handle deleting the queue.
     *
     * @param CapitalCityBuildingQueue $queue
     * @param Character $character
     * @param Kingdom $kingdom
     * @param Carbon $time
     * @return array|string[]
     */
    private function handleDeleteQueue(CapitalCityBuildingQueue $queue, Character $character, Kingdom $kingdom, Carbon $time): array
    {
        $buildingIds = $this->getBuildingIdsForCancellation($queue);

        if (empty($buildingIds)) {
            return $this->errorResult('Nothing to cancel for this queue. Maybe it\'s done or are we currently requesting resources?');
        }

        $this->storeCancellationData($buildingIds, $kingdom, $character, $queue, $time);


        return $this->successResult(['message' => 'Request cancellation for all buildings has been sent off. You can see this in the building queue table.']);
    }

    /**
     * Handle a single building request.
     *
     * @param CapitalCityBuildingQueue $queue
     * @param Character $character
     * @param Kingdom $kingdom
     * @param int $buildingToDelete
     * @param Carbon $time
     * @return array
     */
    private function handleSingleBuildingCancel(CapitalCityBuildingQueue $queue, Character $character, Kingdom $kingdom, int $buildingToDelete, Carbon $time): array
    {
        $capitalCityBuildingCancellation = CapitalCityBuildingCancellation::create([
            'building_id' => $buildingToDelete,
            'kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_building_queue_id' => $queue->id,
            'status' => CapitalCityQueueStatus::TRAVELING,
            'request_kingdom_id' => $queue->requested_kingdom,
            'travel_time_completed_at' => $time,
        ]);

        CapitalCityBuildingRequestCancellationMovement::dispatch($capitalCityBuildingCancellation->id, $queue->id, $queue->character_id, ['building_ids' => [$buildingToDelete]])->delay($time);

        event(new UpdateCapitalCityBuildingQueueTable($character->refresh(), $kingdom));

        return $this->successResult(['message' => 'Request cancellation for the specified building has been sent off. You can see this in the building queue table.']);
    }

    /**
     * Update the building queue data.
     *
     * @param CapitalCityBuildingQueue $queue
     * @param int|null $buildingToDelete
     * @return void
     */
    private function updateBuildingRequestData(CapitalCityBuildingQueue $queue, ?int $buildingToDelete): void {
        $buildingRequestData = $queue->building_request_data;
        foreach ($buildingRequestData as $index => $data) {
            if ($data['building_id'] === $buildingToDelete) {
                $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::CANCELLED;
                break;
            }
        }
        $queue->update(['building_request_data' => $buildingRequestData]);
    }

    /**
     * Possibly delete the actual queue if everything is "cancelled"
     *
     * @param CapitalCityBuildingQueue $queue
     * @return bool
     */
    private function possiblyDeleteBuildingQueue(CapitalCityBuildingQueue $queue): bool {
        $buildingRequestData = $queue->building_request_data;
        $canceledData = array_filter($buildingRequestData, fn($data) => $data['secondary_status'] === CapitalCityQueueStatus::CANCELLED);

        if (count($canceledData) === count($buildingRequestData)) {
            $queue->delete();
            return true;
        }

        return false;
    }

    /**
     * Get the building ids for cancellation.
     *
     * @param CapitalCityBuildingQueue $queue
     * @return array
     */
    private function getBuildingIdsForCancellation(CapitalCityBuildingQueue $queue): array {
        return array_column(array_filter($queue->building_request_data, fn($data) => $data['secondary_status'] === CapitalCityQueueStatus::BUILDING || $data['secondary_status'] === CapitalCityQueueStatus::REPAIRING), 'building_id');
    }

    /**
     * Store cancellation Data.
     *
     * @param array $buildingIds
     * @param Kingdom $kingdom
     * @param Character $character
     * @param CapitalCityBuildingQueue $queue
     * @param Carbon $time
     * @return void
     */
    private function storeCancellationData(array $buildingIds, Kingdom $kingdom, Character $character, CapitalCityBuildingQueue $queue, Carbon $time): void {
        $cancellationData = array_map(fn($id) => [
            'building_id' => $id,
            'kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_building_queue_id' => $queue->id,
            'status' => CapitalCityQueueStatus::TRAVELING,
            'request_kingdom_id' => $queue->requested_kingdom,
            'travel_time_completed_at' => $time,
        ], $buildingIds);

        foreach ($cancellationData as $data) {

            $capitalCityBuildingCancellation = CapitalCityBuildingCancellation::create($data);

            CapitalCityBuildingRequestCancellationMovement::dispatch($capitalCityBuildingCancellation->id, $queue->id, $queue->character_id, ['building_ids' => $buildingIds])->delay($time);

            event(new UpdateCapitalCityBuildingQueueTable($character->refresh(), $kingdom));
        }
    }
}
