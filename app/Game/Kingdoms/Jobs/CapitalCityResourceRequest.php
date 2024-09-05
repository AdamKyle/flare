<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest as CapitalCityResourceRequestModel;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CapitalCityResourceRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected readonly int $capitalCityQueueId, protected readonly int $characterId, protected string $type ) {}

    public function handle(): void
    {

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
            'kingdom_requesting_id', $queueData->requested_kingdom,
        )->first();

        $resourcesForKingdom = $capitalCityResourceRequestData->resources;

        $requestingKingdom = $capitalCityResourceRequestData->requestingKingdom;

        foreach ($resourcesForKingdom as $resourceName => $resourceAmount) {
            $newAmount = $requestingKingdom->{'current_'.$resourceName} + $resourceAmount;

            if ($newAmount > $requestedKingdom->{'max_'.$resourceName}) {
                $newAmount = $requestedKingdom->{'max_'.$resourceName};
            }

            $requestedKingdom->{'current_'.$resourceName} = $newAmount;

            $requestedKingdom->save();

            $requestedKingdom = $requestedKingdom->refresh();
        }

        if ($this->type === CapitalCityResourceRequestType::UNIT_QUEUE) {
            $unitRequestData = $queueData->unit_request_data;

            $updatedUnits = collect($unitRequestData)->transform(function ($unit) {
                return $unit['secondary_status'] === CapitalCityQueueStatus::REQUESTING
                    ? array_merge($unit, ['secondary_status' => CapitalCityQueueStatus::RECRUITING])
                    : $unit;
            });

            $queueData->update([
                'unit_request_data' => $updatedUnits,
            ]);

            $queueData = $queueData->refresh();

            event(new UpdateCapitalCityUnitQueueTable($queueData->character->refresh()));

            dump('Process the recruiting of the units.');

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
            ]);

            $queueData = $queueData->refresh();

            event(new UpdateCapitalCityBuildingQueueTable($queueData->character->refresh()));

            dump('Process the building or repairing of the buildings.');

            return;
        }

        throw new Exception(
            'Could not determine what to do with the resources.'
        );
    }
}
