<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CapitalCityBuildingRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $resourceTypes = [
        'wood',
        'clay',
        'stone',
        'iron',
    ];

    public function __construct(protected readonly int $capitalCityQueueId) {}

    public function handle(CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler): void
    {

        $queueData = CapitalCityBuildingQueue::find($this->capitalCityQueueId);

        if (is_null($queueData)) {
            return;
        }

        if (!$queueData->completed_at->lessThanOrEqualTo(now())) {
            $timeLeft = $queueData->completed_at->diffInMinutes(now());

            if ($timeLeft >= 1) {
                if ($timeLeft <= 15) {
                    $time = now()->addMinutes($timeLeft);
                } else {
                    $time = now()->addMinutes(15);
                }

                // @codeCoverageIgnoreStart
                CapitalCityBuildingRequest::dispatch(
                    $this->capitalCityQueueId,
                )->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $this->handleBuilding($queueData, $capitalCityKingdomLogHandler);
    }

    private function handleBuilding(CapitalCityBuildingQueue $queueData, CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler): void
    {
        $buildingRequestData = $queueData->building_request_data;
        $kingdom = $queueData->kingdom;

        $invalidSecondaryTypes = [
            CapitalCityQueueStatus::REJECTED,
            CapitalCityQueueStatus::REQUESTING,
            CapitalCityQueueStatus::CANCELLED,
        ];

        foreach ($buildingRequestData as $index => $requestData) {

            if (in_array($requestData['secondary_status'], $invalidSecondaryTypes)) {
                continue;
            }

            $building = $kingdom->buildings()->find($requestData['building_id']);

            if ($requestData['type'] === 'upgrade') {
                $this->handleUpgradingBuilding($building, $requestData['to_level']);

                $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;

                continue;
            }

            if ($requestData['type'] === 'repair') {
                $this->handleRebuildingBuilding($building);

                $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::FINISHED;
            }
        }

        $queueData->update([
            'building_request_data' => $buildingRequestData,
            'status' => CapitalCityQueueStatus::FINISHED
        ]);

        $queueData = $queueData->refresh();

        $capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($queueData);
    }

    private function handleUpgradingBuilding(KingdomBuilding $building, int $toLevel): void
    {

        if ($building->gives_resources) {
            $type = $this->getResourceType($building);

            $building->kingdom->{'max_' . $type} += 1000;
            $building->kingdom->save();
        }

        $building->update(['level' => $toLevel]);

        $building = $building->refresh();

        $building->update([
            'current_defence'    => $building->defence,
            'current_durability' => $building->durability,
            'max_defence'        => $building->defence,
            'max_durability'     => $building->durability,
        ]);

        if ($building->is_farm) {
            $building->kingdom->increment('max_population', ($building->level * 100) + 100);
        }
    }

    private function handleRebuildingBuilding(KingdomBuilding $building): void
    {
        $building->update([
            'current_durability' => $this->building->max_durability,
        ]);

        $building = $building->refresh();
        $kingdom = $building->kingdom;

        if ($building->morale_increase > 0) {

            $newMorale = $kingdom->current_morale + $this->building->morale_increase;

            if ($newMorale > 1) {
                $newMorale = 1;
            }

            $kingdom->update([
                'current_morale' => $newMorale,
            ]);
        }
    }


    private function getResourceType(KingdomBuilding $building)
    {
        return collect($this->resourceTypes)->first(fn($type) => $building->{'increase_in_' . $type} !== 0.0);
    }
}
