<?php

namespace App\Game\Kingdoms\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest as CapitalCityResourceRequestModel;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessBuildingRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessUnitRequestHandler;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use Illuminate\Support\Facades\Log;

class CapitalCityResourceRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected readonly int $capitalCityQueueId, protected readonly int $characterId, protected string $type) {}

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
            $timeLeft = $queueData->completed_at->diffInMinutes(now());

            if ($timeLeft >= 1) {
                if ($timeLeft <= 15) {
                    $time = now()->addMinutes($timeLeft);
                } else {
                    $time = now()->addMinutes(15);
                }

                // @codeCoverageIgnoreStart
                CapitalCityBuildingRequestMovement::dispatch(
                    $this->capitalCityQueueId,
                    $this->characterId,
                    $this->type
                )->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $capitalCityResourceRequestData = CapitalCityResourceRequestModel::where(
            'kingdom_requesting_id',
            $queueData->kingdom_id,
        )->first();

        $resourcesForKingdom = $capitalCityResourceRequestData->resources;

        $requestingKingdom = $capitalCityResourceRequestData->requestingKingdom;

        foreach ($resourcesForKingdom as $resourceName => $resourceAmount) {
            $newAmount = $requestingKingdom->{'current_' . $resourceName} + $resourceAmount;

            if ($newAmount > $requestingKingdom->{'max_' . $resourceName}) {
                $newAmount = $requestingKingdom->{'max_' . $resourceName};
            }

            $requestingKingdom->{'current_' . $resourceName} = $newAmount;
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
}
