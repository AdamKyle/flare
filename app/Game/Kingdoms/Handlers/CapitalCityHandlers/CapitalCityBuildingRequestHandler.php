<?php

namespace App\Game\Kingdoms\Handlers\CapitalCityHandlers;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Handlers\Traits\CanAffordPopulationCost;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequest;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\PurchasePeopleService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Skills\Values\SkillTypeValue;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use Carbon\Carbon;

class CapitalCityBuildingRequestHandler {

    use CanAffordPopulationCost;

    private array $messages = [];

    public function __construct(
        private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        private readonly KingdomBuildingService $kingdomBuildingService,
        private readonly PurchasePeopleService $purchasePeopleService,
        private readonly UpdateKingdom $updateKingdom,
    ){}

    /**
     * Create an upgrade or repair request for a character.
     *
     * @param Character $character
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Kingdom $kingdom
     * @param array $buildingsToUpgradeOrRepair
     *
     * @return void
     */
    public function createUpgradeOrRepairRequest(
        Character $character,
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        Kingdom $kingdom,
        array $buildingsToUpgradeOrRepair
    ): void {
        $timeTillFinished = 0;
        $timeToStart = now();

        foreach ($buildingsToUpgradeOrRepair as $index => $buildingRequest) {
            if ($this->shouldRejectBuildingRequest($buildingRequest, $kingdom, $index, $buildingsToUpgradeOrRepair)) {
                continue;
            }

            $building = $kingdom->buildings()->find($buildingRequest['building_id']);
            $minutesToRebuild = $this->calculateRebuildTime($building, $buildingRequest['secondary_status']);

            $timeToComplete = $timeToStart->clone()->addMinutes($minutesToRebuild);
            $timeTillFinished += $minutesToRebuild;

            if ($buildingRequest['secondary_status'] === CapitalCityQueueStatus::REPAIRING) {
                $this->kingdomBuildingService->updateKingdomResourcesForRebuildKingdomBuilding($building);
            } else {
                $this->kingdomBuildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);
            }

            $this->queueBuildingRequest($character, $kingdom, $building, $buildingRequest, $timeToStart, $timeToComplete);
        }

        dump('createUpgradeOrRepairRequest - time till finished for $capitalCityBuildingQueue');
        dump($timeTillFinished);

        $capitalCityBuildingQueue->update([
            'building_request_data' => $buildingsToUpgradeOrRepair,
            'messages' => $this->messages,
            'started_at' => $timeToStart,
            'completed_at' => $timeToStart->clone()->addMinutes($timeTillFinished),
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

        if (ResourceValidation::shouldRedirectKingdomBuilding($building, $kingdom)) {
            $missingResources = ResourceValidation::getMissingCosts($building, $kingdom);

            if (!array_key_exists('population', $missingResources)) {
                if (!$this->canAffordPopulationCost($kingdom, $missingResources['population'])) {
                    $this->messages[] = $building->name . ' has been rejected: Cannot afford ' . $missingResources['population'] . ' population.';
                    $buildingsToUpgradeOrRepair[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                    return true;
                }
            }

            if (count($missingResources) > 0) {
                $this->messages[] = $building->name . ' has been rejected: Not enough resources to upgrade or repair.';
                $buildingsToUpgradeOrRepair[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                return true;
            }

            $this->purchasePeopleService->setKingdom($kingdom)->purchasePeople($missingResources['population']);
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
     * Queue a building upgrade or repair request.
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param KingdomBuilding $building
     * @param array $buildingRequest
     * @param Carbon $timeToStart
     * @param Carbon $timeToComplete
     *
     * @return void
     */
    private function queueBuildingRequest(
        Character $character,
        Kingdom $kingdom,
        KingdomBuilding $building,
        array $buildingRequest,
        Carbon $timeToStart,
        Carbon $timeToComplete
    ): void {
        $type = $buildingRequest['secondary_status'] === CapitalCityQueueStatus::REPAIRING
            ? BuildingQueueType::REPAIR
            : BuildingQueueType::UPGRADE;

        BuildingInQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => $buildingRequest['to_level'],
            'paid_with_gold' => false,
            'paid_amount' => 0,
            'completed_at' => $timeToComplete,
            'started_at' => $timeToStart,
            'type' => $type,
        ]);
    }

    /**
     * Update the capital city building queue.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Carbon $timeToStart
     * @param int $timeTillFinished
     *
     * @return void
     */
    private function updateBuildingQueue(CapitalCityBuildingQueue $capitalCityBuildingQueue, Carbon $timeToStart, int $timeTillFinished): void
    {
        $totalDelayTime = $timeToStart->clone()->addMinutes($timeTillFinished);

        $capitalCityBuildingQueue->update([
            'start' => $timeToStart,
            'completed_at' => $totalDelayTime,
        ]);

        $capitalCityBuildingQueue->refresh();
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
            CapitalCityBuildingRequest::dispatch($capitalCityBuildingQueue->id)->delay(
                $timeTillFinished >= 15 ? $timeToStart->clone()->addMinutes(15) : $timeTillFinished
            );

            $this->updateKingdom->updateKingdom($capitalCityBuildingQueue->kingdom);

            event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character->refresh()));
        } else {
            $this->capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($capitalCityBuildingQueue);
        }
    }
}
