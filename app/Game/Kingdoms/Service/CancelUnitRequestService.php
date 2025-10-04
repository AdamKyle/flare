<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

class CancelUnitRequestService
{
    use ResponseBuilder;

    public function __construct(private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler) {}

    /**
     * Handle the cancelation of a unit recruitment request.
     */
    public function handleCancelRequest(Character $character, Kingdom $kingdom, array $requestData): array
    {

        $queueId = $requestData['queue_id'];
        $queue = CapitalCityUnitQueue::where('kingdom_id', $kingdom->id)
            ->where('character_id', $character->id)
            ->where('id', $queueId)
            ->first();

        if (is_null($queue)) {
            return $this->errorResult('What are you trying to cancel child?');
        }

        if ($queue->completed_at->diffInSeconds(now()) <= 60) {
            return $this->errorResult('You cannot cancel this request because it is on the doorstep of: '.$queue->kingdom->name);
        }

        $unitToDelete = $requestData['unit_name'] ?? null;

        if ($queue->status === CapitalCityQueueStatus::TRAVELING) {

            if (! is_null($unitToDelete)) {
                return $this->cancelUnitRequest($queue, $unitToDelete);
            }

            return $this->cancelAllUnits($queue);
        }

        return $this->errorResult('Your request is no longer traveling. As a result, you are not allowed to cancel the request because that could throw the kingdom into chaos.');
    }

    /**
     * Cancel a single unit.
     */
    private function cancelUnitRequest(CapitalCityUnitQueue $capitalCityUnitQueue, string $unitName): array
    {

        $requestData = $capitalCityUnitQueue->unit_request_data;

        foreach ($requestData as $index => $unitData) {
            if ($unitData['name'] === $unitName) {

                if ($unitData['secondary_status'] !== CapitalCityQueueStatus::TRAVELING) {
                    return $this->errorResult('Cannot cancel: '.$unitName.' for kingdom: '.$capitalCityUnitQueue->kingdom->name.' because it is no longer Traveling.');
                }

                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::CANCELLED;
            }
        }

        $messages = $capitalCityUnitQueue->messages ?? [];

        $capitalCityUnitQueue->update([
            'unit_request_data' => $requestData,
            'messages' => array_merge(
                $messages,
                [
                    $unitName.' Has been canceled at your request while the orders were still traveling.',
                ]
            ),
        ]);

        $capitalCityUnitQueue = $capitalCityUnitQueue->refresh();

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForUnitQueue($capitalCityUnitQueue);

        event(new UpdateCapitalCityUnitQueueTable($capitalCityUnitQueue->character));

        return $this->successResult([
            'message' => 'Successfully canceled the '
                .$unitName
                .'. This will appear as canceled in your queue - assumign you have anything left for this kingdom: '
                .$capitalCityUnitQueue->kingdom->name
                .' If there is nothing left for this kingdom, check your log.',
        ]);
    }

    /**
     * Cancel all valid units in a request queue.
     */
    private function cancelAllUnits(CapitalCityUnitQueue $capitalCityUnitQueue): array
    {
        $requestData = $capitalCityUnitQueue->unit_request_data;

        $hasUnitsTraveling = collect($requestData)
            ->contains(fn ($item) => $item['secondary_status'] === CapitalCityQueueStatus::TRAVELING);

        if (! $hasUnitsTraveling) {
            return $this->errorResult(
                'Cannot cancel unit request(s) for kingdom: '.$capitalCityUnitQueue->kingdom->name.' because none of them are traveling. Are you trying to throw the kingdom into chaos?'
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

        $capitalCityUnitQueue->update([
            'unit_request_data' => $requestData,
            'messages' => array_merge(
                $messages,
                [
                    'All valid requests (those that were still taveling) for this order have been canceled.',
                ]
            ),
        ]);

        $capitalCityUnitQueue = $capitalCityUnitQueue->refresh();

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForUnitQueue($capitalCityUnitQueue);

        event(new UpdateCapitalCityUnitQueueTable($capitalCityUnitQueue->character));

        return $this->successResult([
            'message' => 'Successfully canceled the valid requests for: '
                .$capitalCityUnitQueue->kingdom->name.'.',
        ]);
    }
}
