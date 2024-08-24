<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestCancellationMovement;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Carbon\Carbon;

class CancelUnitRequestService
{
    use ResponseBuilder;

    public function __construct(private readonly UnitMovementService $unitMovementService) {}

    /**
     * Handle the unit request cancellation.
     */
    public function handleCancelRequest(Character $character, Kingdom $kingdom, array $requestData): array
    {
        $queue = $this->getUnitQueue($character, $kingdom, $requestData['capital_city_unit_queue_id']);

        if (is_null($queue)) {
            return $this->errorResult('What are you trying to cancel child?');
        }

        $deleteQueue = $requestData['delete_queue'];
        $unitToDelete = $requestData['unit_id'] ?? null;
        $time = $this->calculateTime($character, $queue);

        if ($queue->status === CapitalCityQueueStatus::TRAVELING) {
            return $this->handleTravelingQueue($queue, $deleteQueue, $unitToDelete, $character, $kingdom);
        }

        if ($deleteQueue) {
            return $this->handleDeleteQueue($queue, $character, $kingdom, $time);
        }

        if (! is_null($unitToDelete)) {
            return $this->handleSingleUnitCancel($queue, $character, $kingdom, $unitToDelete, $time);
        }

        return $this->errorResult('Something is wrong. Nothing was done.');
    }

    /**
     * Get the unit queue.
     */
    private function getUnitQueue(Character $character, Kingdom $kingdom, int $queueId): ?CapitalCityUnitQueue
    {
        return CapitalCityUnitQueue::where('id', $queueId)
            ->where('character_id', $character->id)
            ->where('kingdom_id', $kingdom->id)
            ->first();
    }

    /**
     * Calculate the time required to travel to the kingdom.
     */
    private function calculateTime(Character $character, CapitalCityUnitQueue $queue): Carbon
    {
        $time = $this->unitMovementService->determineTimeRequired(
            $character,
            $queue->kingdom,
            $queue->requested_kingdom,
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION
        );

        return now()->addMinutes($time);
    }

    /**
     * Handle deleting a queue or multiple queues.
     */
    private function handleTravelingQueue(CapitalCityUnitQueue $queue, bool $deleteQueue, ?int $unitToDelete, Character $character, Kingdom $kingdom): array
    {
        if ($deleteQueue) {
            $queue->delete();

            event(new UpdateCapitalCityUnitQueueTable($character));

            return $this->successResult(['message' => 'All orders have been canceled.']);
        }

        $this->updateUnitRequestData($queue, $unitToDelete);
        $deleted = $this->possiblyDeleteUnitQueue($queue->refresh());
        event(new UpdateCapitalCityUnitQueueTable($character));

        $message = $deleted ? 'The last of your orders has been canceled.' : 'The selected unit has been stricken from the request.';

        return $this->successResult(['message' => $message]);
    }

    /**
     * Handle deleting the queue.
     *
     * @return array|string[]
     */
    private function handleDeleteQueue(CapitalCityUnitQueue $queue, Character $character, Kingdom $kingdom, Carbon $time): array
    {
        $unitIds = $this->getUnitIdsForCancellation($queue);

        if (empty($unitIds)) {
            return $this->errorResult('Nothing to cancel for this queue. Maybe it\'s done or are we currently requesting resources?');
        }

        $this->storeCancellationData($unitIds, $kingdom, $character, $queue, $time);

        return $this->successResult(['message' => 'Request cancellation for all units has been sent off. You can see this in the unit queue table.']);
    }

    /**
     * Handle a single unit request.
     */
    private function handleSingleUnitCancel(CapitalCityUnitQueue $queue, Character $character, Kingdom $kingdom, int $unitToDelete, Carbon $time): array
    {
        $capitalCityUnitCancellation = CapitalCityUnitCancellation::create([
            'unit_id' => $unitToDelete,
            'kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_unit_queue_id' => $queue->id,
            'status' => CapitalCityQueueStatus::TRAVELING,
            'request_kingdom_id' => $queue->requested_kingdom,
            'travel_time_completed_at' => $time,
        ]);

        CapitalCityUnitRequestCancellationMovement::dispatch($capitalCityUnitCancellation->id, $queue->id, $queue->character_id, ['unit_ids' => [$unitToDelete]])->delay($time);

        event(new UpdateCapitalCityUnitQueueTable($character));

        return $this->successResult(['message' => 'Request cancellation for the specified unit has been sent off. You can see this in the unit queue table.']);
    }

    /**
     * Update the unit queue data.
     */
    private function updateUnitRequestData(CapitalCityUnitQueue $queue, ?int $unitToDelete): void
    {
        $unitRequestData = $queue->unit_request_data;

        foreach ($unitRequestData as $index => $data) {
            $gameUnit = GameUnit::where('name', $data['name'])->first();

            if ($gameUnit->id === $unitToDelete) {
                $unitRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::CANCELLED;
                break;
            }
        }
        $queue->update(['unit_request_data' => $unitRequestData]);
    }

    /**
     * Possibly delete the actual queue if everything is "cancelled"
     */
    private function possiblyDeleteUnitQueue(CapitalCityUnitQueue $queue): bool
    {
        $unitRequestData = $queue->unit_request_data;
        $canceledData = array_filter($unitRequestData, fn ($data) => $data['secondary_status'] === CapitalCityQueueStatus::CANCELLED);

        if (count($canceledData) === count($unitRequestData)) {
            $queue->delete();

            return true;
        }

        return false;
    }

    /**
     * Get the unit ids for cancellation.
     */
    private function getUnitIdsForCancellation(CapitalCityUnitQueue $queue): array
    {
        $names = array_column(array_filter($queue->unit_request_data, fn ($data) => $data['secondary_status'] === CapitalCityQueueStatus::RECRUITING), 'name');

        $gameUnitIds = GameUnit::whereIn('name', $names)->pluck('id')->toarray();

        return $gameUnitIds;
    }

    /**
     * Store cancellation Data.
     */
    private function storeCancellationData(array $unitIds, Kingdom $kingdom, Character $character, CapitalCityUnitQueue $queue, Carbon $time): void
    {
        $cancellationData = array_map(fn ($id) => [
            'unit_id' => $id,
            'kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_unit_queue_id' => $queue->id,
            'status' => CapitalCityQueueStatus::TRAVELING,
            'request_kingdom_id' => $queue->requested_kingdom,
            'travel_time_completed_at' => $time,
        ], $unitIds);

        foreach ($cancellationData as $data) {

            $capitalCityUnitCancellation = CapitalCityUnitCancellation::create($data);

            CapitalCityUnitRequestCancellationMovement::dispatch($capitalCityUnitCancellation->id, $queue->id, $queue->character_id, ['unit_ids' => $unitIds])->delay($time);

            event(new UpdateCapitalCityUnitQueueTable($character));
        }
    }
}
