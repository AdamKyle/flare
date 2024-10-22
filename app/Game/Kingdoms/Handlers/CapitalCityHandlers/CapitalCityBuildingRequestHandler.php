<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use Carbon\Carbon;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Handlers\Traits\CanAffordPopulationCost;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequest;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\PurchasePeopleService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Validation\KingdomBuildingResourceValidation;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

class CapitalCityBuildingRequestHandler
{

    use CanAffordPopulationCost;

    private array $messages = [];

    public function __construct(
        private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        private readonly KingdomBuildingService $kingdomBuildingService,
        private readonly KingdomBuildingResourceValidation $kingdomBuildingResourceValidation,
        private readonly PurchasePeopleService $purchasePeopleService,
        private readonly UpdateKingdom $updateKingdom,
    ) {}

    /**
     * Create an upgrade or repair request for a character.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Kingdom $kingdom
     * @param array $buildingsToUpgradeOrRepair
     *
     * @return void
     */
    public function createUpgradeOrRepairRequest(
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        Kingdom $kingdom,
        array $buildingsToUpgradeOrRepair
    ): void {
        $timeTillFinished = 0;
        $timeToStart = now();

        $upgrading = true;

        foreach ($buildingsToUpgradeOrRepair as $index => $buildingRequest) {
            if ($this->shouldRejectBuildingRequest($buildingRequest, $kingdom, $index, $buildingsToUpgradeOrRepair)) {
                continue;
            }

            $building = $kingdom->buildings()->find($buildingRequest['building_id']);
            $minutesToRebuild = $this->calculateRebuildTime($building, $buildingRequest['secondary_status']);

            $timeTillFinished += $minutesToRebuild;

            if ($buildingRequest['secondary_status'] === CapitalCityQueueStatus::REPAIRING) {
                $upgrading = false;
                $this->kingdomBuildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);
            } else {
                $this->kingdomBuildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);
            }
        }

        if (config('app.env') !== 'production') {
            $timeTillFinished = 1;
        }

        $capitalCityBuildingQueue->update([
            'building_request_data' => $buildingsToUpgradeOrRepair,
            'messages' => $this->messages,
            'started_at' => $timeToStart,
            'completed_at' => $timeToStart->clone()->addMinutes($timeTillFinished),
            'status' => $upgrading ? CapitalCityQueueStatus::BUILDING : CapitalCityQueueStatus::REPAIRING
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        $this->dispatchOrLogBuildingRequest($capitalCityBuildingQueue, $buildingsToUpgradeOrRepair, $timeToStart, $timeTillFinished);
    }

    /**
     * Determine if a building request should be rejected.
     *
     * @param array $buildingRequest
     * @param Kingdom $kingdom
     * @param int $index
     * @param array $buildingsToUpgradeOrRepair
     *
     * @return bool
     */
    private function shouldRejectBuildingRequest(array $buildingRequest, Kingdom $kingdom, int $index, array &$buildingsToUpgradeOrRepair): bool
    {
        $building = $kingdom->buildings()->where('id', $buildingRequest['building_id'])->first();

        if ($this->kingdomBuildingResourceValidation->isMissingResources($building)) {
            $buildingCosts = $this->kingdomBuildingResourceValidation->getCostsForBuilding($building);
            $missingResources = $this->kingdomBuildingResourceValidation->getMissingCosts($kingdom, $buildingCosts);

            if (array_key_exists('population', $missingResources)) {
                if (!$this->canAffordPopulationCost($kingdom, $missingResources['population'])) {
                    $this->messages[] = $building->name . ' has been rejected: Cannot afford ' . $missingResources['population'] . ' population.';
                    $buildingsToUpgradeOrRepair[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                    return true;
                }

                $this->purchasePeopleService->setKingdom($kingdom)->purchasePeople($missingResources['population']);

                return false;
            }

            if (count($missingResources) > 0) {
                $this->messages[] = $building->name . ' has been rejected: Not enough resources to upgrade or repair.';
                $buildingsToUpgradeOrRepair[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate the rebuild time for a building.
     *
     * @param KingdomBuilding $building
     * @param string $secondaryStatus
     * @return int
     */
    private function calculateRebuildTime(KingdomBuilding $building, string $secondaryStatus): int
    {

        $timeReduction = $building->kingdom->fetchKingBasedSkillValue('building_time_reduction');

        if ($secondaryStatus === CapitalCityQueueStatus::REPAIRING) {
            $minutesToRebuild = $building->rebuild_time;

            return $minutesToRebuild - ($minutesToRebuild * $timeReduction);
        }

        return (int) floor($building->time_increase - $building->time_increase * $timeReduction);
    }

    /**
     * Dispatch or log the building request.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param array $buildingsToUpgradeOrRepair
     * @param Carbon $timeToStart
     * @param int $timeTillFinished
     *
     * @return void
     */
    private function dispatchOrLogBuildingRequest(
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        array $buildingsToUpgradeOrRepair,
        Carbon $timeToStart,
        int $timeTillFinished
    ): void {
        $filteredRequestData = collect($buildingsToUpgradeOrRepair)->filter(fn($item) => in_array($item['secondary_status'], [
            CapitalCityQueueStatus::BUILDING,
            CapitalCityQueueStatus::REPAIRING
        ]))->values()->toArray();

        if (!empty($filteredRequestData)) {

            if (config('app.env') !== 'production') {
                $timeTillFinished = 1;
            }

            CapitalCityBuildingRequest::dispatch($capitalCityBuildingQueue->id)->delay(
                ($timeTillFinished >= 15 ? $timeToStart->clone()->addMinutes(15) : $timeToStart->clone()->addMinutes($timeTillFinished))
            );

            $this->updateKingdom->updateKingdom($capitalCityBuildingQueue->kingdom);

            event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character->refresh()));
        } else {
            $this->capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($capitalCityBuildingQueue);
        }
    }
}
