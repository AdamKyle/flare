<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest as CapitalCityResourceRequestModel;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessBuildingRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessUnitRequestHandler;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CapitalCityResourceRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected readonly int $capitalCityQueueId, protected readonly int $resourceRequestId, protected string $type) {}

    public function handle(
        CapitalCityProcessBuildingRequestHandler $capitalCityProcessBuildingRequestHandler,
        CapitalCityProcessUnitRequestHandler $capitalCityProcessUnitRequestHandler
    ): void {

        $queueData = null;

        if ($this->type === CapitalCityResourceRequestType::BUILDING_QUEUE) {
            $queueData = CapitalCityBuildingQueue::find($this->capitalCityQueueId);
        }

        if ($this->type === CapitalCityResourceRequestType::UNIT_QUEUE) {
            $queueData = CapitalCityUnitQueue::find($this->capitalCityQueueId);
        }

        if (is_null($queueData)) {
            return;
        }

        if (! $queueData->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = now()->diffInMinutes($queueData->completed_at);

            if ($timeLeft >= 1) {
                if ($timeLeft <= 15) {
                    $time = now()->addMinutes($timeLeft);
                } else {
                    $time = now()->addMinutes(15);
                }

                // @codeCoverageIgnoreStart
                CapitalCityResourceRequest::dispatch(
                    $this->capitalCityQueueId,
                    $this->resourceRequestId,
                    $this->type
                )->onConnection('long_running')->onQueue('default_long')->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $capitalCityResourceRequestData = CapitalCityResourceRequestModel::find($this->resourceRequestId);

        if (is_null($capitalCityResourceRequestData)) {
            $this->markQueueAsRejected($queueData);

            return;
        }

        if (! $capitalCityResourceRequestData->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = now()->diffInMinutes($capitalCityResourceRequestData->completed_at);

            if ($timeLeft <= 15) {
                $time = now()->addMinutes($timeLeft);
            } else {
                $time = now()->addMinutes(15);
            }

            CapitalCityResourceRequest::dispatch(
                $this->capitalCityQueueId,
                $this->resourceRequestId,
                $this->type
            )->onConnection('long_running')->onQueue('default_long')->delay($time);

            return;
        }

        $resourcesForKingdom = $capitalCityResourceRequestData->resources;

        $requestingKingdom = $capitalCityResourceRequestData->requestingKingdom;

        foreach ($resourcesForKingdom as $resourceName => $resourceAmount) {
            $newAmount = $requestingKingdom->{'current_'.$resourceName} + $resourceAmount;

            if ($newAmount > $requestingKingdom->{'max_'.$resourceName}) {
                $newAmount = $requestingKingdom->{'max_'.$resourceName};
            }

            $requestingKingdom->{'current_'.$resourceName} = $newAmount;
        }

        $requestingKingdom->save();

        if ($this->type === CapitalCityResourceRequestType::UNIT_QUEUE) {
            $unitRequestData = $queueData->unit_request_data;

            $updatedUnits = collect($unitRequestData)->transform(function ($unit) {
                return $unit['secondary_status'] === CapitalCityQueueStatus::REQUESTING
                    ? array_merge($unit, ['secondary_status' => CapitalCityQueueStatus::RECRUITING])
                    : $unit;
            });

            $queueData->update([
                'unit_request_data' => $updatedUnits,
                'status' => CapitalCityQueueStatus::RECRUITING,
            ]);

            $queueData = $queueData->refresh();

            $capitalCityResourceRequestData->delete();

            event(new UpdateCapitalCityUnitQueueTable($queueData->character->refresh()));

            $capitalCityProcessUnitRequestHandler->handleUnitRequests($queueData, true);

            return;
        }

        if ($this->type === CapitalCityResourceRequestType::BUILDING_QUEUE) {
            $buildingRequestData = $queueData->building_request_data;

            $updatedBuildingRequestData = collect($buildingRequestData)->transform(function ($buildingData) {
                if ($buildingData['secondary_status'] === CapitalCityQueueStatus::REQUESTING) {
                    $newStatus = ($buildingData['type'] === 'repair')
                        ? CapitalCityQueueStatus::REPAIRING
                        : CapitalCityQueueStatus::BUILDING;

                    return array_merge($buildingData, ['secondary_status' => $newStatus]);
                }

                return $buildingData;
            });

            $queueData->update([
                'building_request_data' => $updatedBuildingRequestData,
                'status' => CapitalCityQueueStatus::BUILDING,
            ]);

            $queueData = $queueData->refresh();

            $capitalCityResourceRequestData->delete();

            event(new UpdateCapitalCityBuildingQueueTable($queueData->character->refresh()));

            Log::channel('capital_city_building_upgrades')->info('Handling Building Requests after requesting resources.');

            $capitalCityProcessBuildingRequestHandler->handleBuildingRequests($queueData, true);

            return;
        }

        throw new Exception(
            'Could not determine what to do with the resources.'
        );
    }

    protected function cleanUpMissingCostsForQueue(CapitalCityUnitQueue|CapitalCityBuildingQueue $queue): CapitalCityUnitQueue|CapitalCityBuildingQueue
    {

        $requestData = [];
        $columToUpdate = null;

        if ($queue instanceof CapitalCityBuildingQueue) {
            $requestData = $queue->building_request_data;
            $columnToUpdate = 'building_request_data';
        }

        if ($queue instanceof CapitalCityUnitQueue) {
            $requestData = $queue->unit_request_data;
            $columnToUpdate = 'unit_request_data';
        }

        foreach ($requestData as $index => $data) {
            $requestData[$index]['missing_costs'] = [];
        }

        $queue->update([
            $columnToUpdate => $requestData,
        ]);

        return $queue->refresh();
    }

    private function markQueueAsRejected(CapitalCityUnitQueue|CapitalCityBuildingQueue $queue): void
    {
        if ($queue instanceof CapitalCityBuildingQueue) {
            $requestData = collect($queue->building_request_data)
                ->map(function ($buildingRequest) {
                    if ($buildingRequest['secondary_status'] === CapitalCityQueueStatus::REQUESTING) {
                        return array_merge($buildingRequest, ['secondary_status' => CapitalCityQueueStatus::REJECTED]);
                    }

                    return $buildingRequest;
                })
                ->toArray();

            $queue->update([
                'building_request_data' => $requestData,
                'status' => CapitalCityQueueStatus::REJECTED,
            ]);

            event(new UpdateCapitalCityBuildingQueueTable($queue->character->refresh()));

            return;
        }

        $requestData = collect($queue->unit_request_data)
            ->map(function ($unitRequest) {
                if ($unitRequest['secondary_status'] === CapitalCityQueueStatus::REQUESTING) {
                    return array_merge($unitRequest, ['secondary_status' => CapitalCityQueueStatus::REJECTED]);
                }

                return $unitRequest;
            })
            ->toArray();

        $queue->update([
            'unit_request_data' => $requestData,
            'status' => CapitalCityQueueStatus::REJECTED,
        ]);

        event(new UpdateCapitalCityUnitQueueTable($queue->character->refresh()));
    }
}
