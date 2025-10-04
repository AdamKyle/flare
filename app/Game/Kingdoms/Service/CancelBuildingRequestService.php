<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

class CancelBuildingRequestService
{
    use ResponseBuilder;

    public function __construct(
        private readonly UnitMovementService $unitMovementService,
        private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler
    ) {}

    /**
     * Handle the cancelation request
     */
    public function handleCancelRequest(Character $character, Kingdom $kingdom, array $requestData): array
    {

        $queueId = $requestData['queue_id'];
        $queue = CapitalCityBuildingQueue::where('kingdom_id', $kingdom->id)
            ->where('character_id', $character->id)
            ->where('id', $queueId)
            ->first();

        if (is_null($queue)) {
            return $this->errorResult('What are you trying to cancel child?');
        }

        if ($queue->completed_at->diffInSeconds(now()) <= 60) {
            return $this->errorResult('You cannot cancel this request because it is on the doorstep of: '.$queue->kingdom->name);
        }

        $buildingToDelete = $requestData['building_id'] ?? null;

        if ($queue->status === CapitalCityQueueStatus::TRAVELING) {

            if (! is_null($buildingToDelete)) {
                return $this->cancelBuildingRequest($queue, $buildingToDelete);
            }

            return $this->cancelAllBuildings($queue);
        }

        return $this->errorResult('Your request is no longer traveling. As a result, you are not allowed to cancel the request because that could throw the kingdom into chaos.');
    }

    /**
     * Cancel a single building request
     */
    private function cancelBuildingRequest(CapitalCityBuildingQueue $capitalCityBuildingQueue, int $buildingId): array
    {

        $requestData = $capitalCityBuildingQueue->building_request_data;

        $buildingNameToCancel = null;

        foreach ($requestData as $index => $buildingRequest) {
            if ($buildingRequest['building_id'] === $buildingId) {

                $buildingNameToCancel = $buildingRequest['building_name'];

                if ($buildingRequest['secondary_status'] !== CapitalCityQueueStatus::TRAVELING) {
                    return $this->errorResult('Cannot cancel: '.$buildingRequest['name'].' for kingdom: '.$capitalCityBuildingQueue->kingdom->name.' because it is no longer Traveling.');
                }

                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::CANCELLED;
            }
        }

        $messages = $capitalCityBuildingQueue->messages ?? [];

        $capitalCityBuildingQueue->update([
            'building_request_data' => $requestData,
            'messages' => array_merge(
                $messages,
                [
                    $buildingNameToCancel.' Has been canceled at your request while the orders were still traveling.',
                ]
            ),
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($capitalCityBuildingQueue);

        event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character));

        return $this->successResult([
            'message' => 'Successfully canceled the '
                .$buildingNameToCancel
                .'. This will appear as canceled in your queue - assumign you have anything left for this kingdom: '
                .$capitalCityBuildingQueue->kingdom->name
                .' If there is nothing left for this kingdom, check your log.',
        ]);
    }

    /**
     * Cancel all buildings in a request
     */
    private function cancelAllBuildings(CapitalCityBuildingQueue $capitalCityBuildingQueue): array
    {
        $requestData = $capitalCityBuildingQueue->building_request_data;

        $hasBuildingsTraveling = collect($requestData)
            ->contains(fn ($item) => $item['secondary_status'] === CapitalCityQueueStatus::TRAVELING);

        if (! $hasBuildingsTraveling) {
            return $this->errorResult(
                'Cannot cancel unit request(s) for kingdom: '.$capitalCityBuildingQueue->kingdom->name.' because none of them are traveling. Are you trying to throw the kingdom into chaos?'
            );
        }

        $requestData = collect($requestData)
            ->map(function ($item) {
                if ($item['secondary_status'] === CapitalCityQueueStatus::TRAVELING) {
                    return array_merge($item, ['secondary_status' => CapitalCityQueueStatus::CANCELLED]);
                }

                return $item;
            })
            ->toArray();

        $messages = $capitalCityUnitQueue->messages ?? [];

        $capitalCityBuildingQueue->update([
            'building_request_data' => $requestData,
            'messages' => array_merge(
                $messages,
                [
                    'All valid requests (those that were still taveling) for this order have been canceled.',
                ]
            ),
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($capitalCityBuildingQueue);

        event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character));

        return $this->successResult([
            'message' => 'Successfully canceled the valid requests for: '
                .$capitalCityBuildingQueue->kingdom->name.'.',
        ]);
    }
}
