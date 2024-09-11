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
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Maps\Calculations\DistanceCalculation;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;

class CapitalCityProcessBuildingRequestHandler {

    use CanAffordPopulationCost;

    /**
     * @var array $messages
     */
    private array $messages = [];

    /**
     * @param CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler
     * @param DistanceCalculation $distanceCalculation
     * @param CapitalCityRequestResourcesHandler $capitalCityRequestResourcesHandler
     * @param CapitalCityBuildingRequestHandler $capitalCityBuildingRequestHandler
     */
    public function __construct(
        private readonly CapitalCityKingdomLogHandler $capitalCityKingdomLogHandler,
        private readonly DistanceCalculation $distanceCalculation,
        private readonly CapitalCityRequestResourcesHandler $capitalCityRequestResourcesHandler,
        private readonly CapitalCityBuildingRequestHandler $capitalCityBuildingRequestHandler,
    ) {}

    /**
     * Handle the building requests.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    public function handleBuildingRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        $requestData = $this->processBuildingRequests($kingdom, $requestData);

        $summedMissingCosts = $this->calculateSummedMissingCosts($requestData);

        if (!empty($summedMissingCosts)) {
            $this->handleResourceRequests($capitalCityBuildingQueue, $character, $summedMissingCosts, $requestData, $kingdom);
        } else {
            $this->handleNoResourceRequests($capitalCityBuildingQueue, $requestData);
        }
    }

    /**
     * Process each building request.
     *
     * @param Kingdom $kingdom
     * @param array $requestData
     * @return array
     */
    private function processBuildingRequests(
        Kingdom $kingdom,
        array $requestData
    ): array
    {
        foreach ($requestData as $index => $buildingUpgradeRequest) {
            $building = $kingdom->buildings()->where('id', $buildingUpgradeRequest['building_id'])->first();
            $buildingUpgradeRequest = $this->processPotentialResourceRequests(
                $kingdom, $building, $buildingUpgradeRequest
            );

            $requestData[$index] = $buildingUpgradeRequest;
        }

        return $requestData;
    }

    /**
     * Process potential resource requests for building upgrades.
     *
     * @param Kingdom $kingdom
     * @param KingdomBuilding $building
     * @param array $buildingUpgradeRequest
     * @return array
     */
    private function processPotentialResourceRequests(
        Kingdom $kingdom,
        KingdomBuilding $building,
        array $buildingUpgradeRequest
    ): array
    {
        if (ResourceValidation::shouldRedirectKingdomBuilding($building, $kingdom)) {
            $missingResources = ResourceValidation::getMissingCosts($building, $kingdom);

            if (empty($missingResources)) {
                $buildingUpgradeRequest['secondary_status'] = CapitalCityQueueStatus::BUILDING;
                return $buildingUpgradeRequest;
            }

            $canAffordPopulation = !isset($missingResources['population']) || $missingResources['population'] === 0;

            if (isset($missingResources['population'])) {
                $canAffordPopulation = $this->canAffordPopulationCost($kingdom, $missingResources['population']);
            }

            if (!$canAffordPopulation) {
                $this->messages[] = $building->name . ' has been rejected for reason of: Cannot afford to use: ' .
                    $kingdom->name . '\'s treasury to purchase an extra: ' .
                    $missingResources['population'] . ' population.';

                $buildingUpgradeRequest['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                return $buildingUpgradeRequest;
            }

            $buildingUpgradeRequest['missing_costs'] = $missingResources;
            $buildingUpgradeRequest['secondary_status'] = CapitalCityQueueStatus::REQUESTING;

            return $buildingUpgradeRequest;
        }

        $buildingUpgradeRequest['secondary_status'] = ($buildingUpgradeRequest['type'] === 'repair' ? CapitalCityQueueStatus::REPAIRING : CapitalCityQueueStatus::BUILDING);

        return $buildingUpgradeRequest;
    }

    /**
     * Calculate the total missing costs.
     *
     * @param array $requestData
     * @return array
     */
    private function calculateSummedMissingCosts(array $requestData): array
    {
        return collect($requestData)
            ->pluck('missing_costs')
            ->map(fn($costs) => collect($costs)->except('population'))
            ->reduce(fn($carry, $costs) => $carry->merge($costs)->map(fn($value, $key) => $carry->get($key, 0) + $value), collect())
            ->toArray();
    }

    /**
     * Handle resource requests if needed.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Character $character
     * @param array $summedMissingCosts
     * @param array $requestData
     * @param Kingdom $kingdom
     * @return void
     */
    private function handleResourceRequests(
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        Character $character,
        array $summedMissingCosts,
        array $requestData,
        Kingdom $kingdom
    ): void
    {
        $this->capitalCityRequestResourcesHandler->handleResourceRequests(
            $capitalCityBuildingQueue,
            $character,
            $summedMissingCosts,
            $requestData,
            $kingdom
        );
    }

    /**
     * Handle the case where no resource requests are needed.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param array $requestData
     * @return void
     */
    private function handleNoResourceRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue, array $requestData): void
    {
        $hasBuildingOrRepairing = collect($requestData)->contains(fn($item) => in_array($item['secondary_status'], [
            CapitalCityQueueStatus::BUILDING,
            CapitalCityQueueStatus::REPAIRING,
            CapitalCityQueueStatus::REQUESTING
        ]));

        if (!$hasBuildingOrRepairing) {
            $this->createLogAndTriggerEvents($capitalCityBuildingQueue);
        } else {
            $this->createUpgradeOrRepairRequest($capitalCityBuildingQueue, $capitalCityBuildingQueue->kingdom, $requestData);
            $this->sendOffEvents($capitalCityBuildingQueue);
        }
    }

    /**
     * Create a log and trigger events if no building or repairing requests are present.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    private function createLogAndTriggerEvents(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        $capitalCityBuildingQueue->update([
            'building_request_data' => $capitalCityBuildingQueue->building_request_data,
            'messages' => $this->messages,
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        $this->capitalCityKingdomLogHandler->possiblyCreateLogForBuildingQueue($capitalCityBuildingQueue);
        $this->sendOffEvents($capitalCityBuildingQueue);
    }

    /**
     * Refresh queue and trigger events.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    private function sendOffEvents(CapitalCityBuildingQueue $capitalCityBuildingQueue): void
    {
        event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character));
    }

    /**
     * Create upgrade requests for buildings.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Kingdom $kingdom
     * @param array $buildingsToUpgradeOrRepair
     * @return void
     */
    private function createUpgradeOrRepairRequest(
        CapitalCityBuildingQueue $capitalCityBuildingQueue,
        Kingdom $kingdom,
        array $buildingsToUpgradeOrRepair
    ): void
    {
        $this->capitalCityBuildingRequestHandler->createUpgradeOrRepairRequest(
            $capitalCityBuildingQueue,
            $kingdom,
            $buildingsToUpgradeOrRepair,
        );
    }
}
