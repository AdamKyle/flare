<?php

namespace App\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Service\KingdomMaxResourceRecalculationService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
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

    public function handle(
        CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        KingdomMaxResourceRecalculationService $kingdomMaxResourceRecalculationService
    ): void
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
                )->onConnection('long_running')->onQueue('default_long')->delay($time);

                return;
                // @codeCoverageIgnoreEnd
            }
        }

        $this->handleBuilding($queueData, $capitalCityKingdomLogHandler, $kingdomMaxResourceRecalculationService);
    }

    private function handleBuilding(
        CapitalCityBuildingQueue $queueData,
        CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        KingdomMaxResourceRecalculationService $kingdomMaxResourceRecalculationService
    ): void
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

            if (is_null($building)) {
                Log::warning('Capital city building request rejected because the queued building is missing.', [
                    'queue_id' => $queueData->id,
                    'kingdom_id' => $kingdom->id,
                    'building_id' => $requestData['building_id'],
                    'request_type' => $requestData['type'],
                ]);

                $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                continue;
            }

            if ($requestData['type'] === 'upgrade') {
                if ($this->isInvalidUpgradeRequest($building, $requestData)) {
                    $buildingRequestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;

                    continue;
                }

                $this->handleUpgradingBuilding($building, $requestData['to_level']);
                $kingdomMaxResourceRecalculationService->recalculate($kingdom);

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

        $building->update(['level' => $toLevel]);

        $building = $building->refresh();

        $building->update([
            'current_defence'    => $building->defence,
            'current_durability' => $building->durability,
            'max_defence'        => $building->defence,
            'max_durability'     => $building->durability,
        ]);

    }

    private function isInvalidUpgradeRequest(KingdomBuilding $building, array $requestData): bool
    {
        return $building->level >= $building->gameBuilding->max_level ||
            (int) $requestData['to_level'] > $building->gameBuilding->max_level ||
            (int) $requestData['from_level'] !== $building->level;
    }

    private function handleRebuildingBuilding(KingdomBuilding $building): void
    {
        $building->update([
            'current_durability' => $building->max_durability,
        ]);

        $building = $building->refresh();
        $kingdom = $building->kingdom;

        if ($building->morale_increase > 0) {

            $newMorale = $kingdom->current_morale + $building->morale_increase;

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
