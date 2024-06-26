<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateBuildingUpgrades;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingUpgrades;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\UnitCosts;

class CapitalCityBuildingManagement {

    use ResponseBuilder;

    private array $messages = [];

    public function __construct(private readonly KingdomBuildingService $kingdomBuildingService,
                                private readonly UnitMovementService $unitMovementService,
                                private readonly ResourceTransferService $resourceTransferService,
                                private readonly UpdateKingdom $updateKingdom) {}

    /**
     *
     * Create the requests
     *
     * @param Character $character
     * @param Kingdom $kingdom
     * @param array $requests
     * @param string $type
     * @return array
     */
    public function createBuildingUpgradeRequestQueue(Character $character, Kingdom $kingdom, array $requests, string $type): array {

        foreach ($requests as $request) {

            $kingdomId = $request['kingdomId'];
            $buildingIds = $request['buildingIds'];

            $buildings = KingdomBuilding::where('kingdom_id', $kingdomId)->whereIn('id', $buildingIds)->get();

            $toKingdom = Kingdom::find($kingdomId);

            $time          = $this->unitMovementService->determineTimeRequired($character, $toKingdom, $kingdomId);

            $minutes       = now()->addMinutes($time);

            $queueData = [
                'kingdom_id' => $kingdomId,
                'building_request_data' => [],
            ];

            foreach ($buildings as $building) {
                $queueData['building_request_data'][] = [
                    'building_id'   => $building->id,
                    'costs'         => $this->kingdomBuildingService->getBuildingCosts($building),
                    'type'          => $type,
                    'missing_costs' => [],
                    'secondary_status' => null,
                ];
            }

            $queueData['character_id'] = $character->id;
            $queueData['status'] = CapitalCityQueueStatus::TRAVELING;
            $queueData['started_at'] = now();
            $queueData['completed_at'] = $minutes;

            $capitalCityBuildingQueue = CapitalCityBuildingQueue::create($queueData);

            CapitalCityBuildingRequestMovement::dispatch($capitalCityBuildingQueue->id, $character->id)->delay($minutes);
        }

        event(new UpdateCapitalCityBuildingUpgrades($character, $kingdom));

        return $this->successResult([
            'message' => 'Building upgrades have been sent off to their respective kingdoms.
            The list below has been updated to reflect kingdoms you can send upgrade requests to. If
            you click: "Building Upgrade/Repair" in the top right, you will see a table of orders and
            their associated statuses.'
        ]);
    }

    /**
     * Process the building request.
     *
     * - If we cannot afford the resources, then get the missing costs and send off the resource requests.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    public function processBuildingRequest(CapitalCityBuildingQueue $capitalCityBuildingQueue): void {
        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        foreach ($requestData as $index => $buildingUpgradeRequest) {
            $building = $kingdom->buildings()->where('id', $buildingUpgradeRequest['building_id'])->first();
            $buildingUpgradeRequest = $this->processPotentialResourceRequests($capitalCityBuildingQueue, $kingdom, $building, $character, $buildingUpgradeRequest);

            $requestData[$index] = $buildingUpgradeRequest;

            $capitalCityBuildingQueue->update([
                'building_request_data' => $requestData,
            ]);

            $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

            if ($buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::REQUESTING) {
                continue;
            }

            // If the request is ready for building, handle it immediately
            if ($buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::BUILDING) {
                $this->handleBuildingRequest($capitalCityBuildingQueue, $building, $character);
            }
        }

        $capitalCityBuildingQueue->update([
            'building_request_data' => $requestData,
            'messages' => $this->messages,
        ]);
    }

    private function handleBuildingRequest(CapitalCityBuildingQueue $capitalCityBuildingQueue, KingdomBuilding $building, Character $character): void {
        $kingdom = $capitalCityBuildingQueue->kingdom;

        if ($this->needsPopulationCost($building)) {
            $population = $building->missing_costs['population'];
            $cost = (new UnitCosts(UnitCosts::PERSON))->fetchCost() * $population;
            $treasury = $kingdom->treasury;

            $treasury -= $cost;

            $kingdom->update([
                'treasury' => $treasury,
            ]);
        }

        $kingdom = $this->kingdomBuildingService->updateKingdomResourcesForRebuildKingdomBuilding($building);
        $this->kingdomBuildingService->upgradeKingdomBuilding($building, $character, $capitalCityBuildingQueue->id);
        $this->updateKingdom->updateKingdom($kingdom);
    }

    private function needsPopulationCost(KingdomBuilding $building): bool {
        return isset($building->missing_costs['population']) && $building->missing_costs['population'] > 0;
    }

    private function canAffordPopulationCost(Kingdom $kingdom, int $populationAmount): bool {
        if ($kingdom->treasury <= 0) {
            return false;
        }

        $cost = (new UnitCosts(UnitCosts::PERSON))->fetchCost() * $populationAmount;

        if ($kingdom->treasury < $cost) {
            return false;
        }

        return true;
    }

    private function processPotentialResourceRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue, Kingdom $kingdom, KingdomBuilding $building, Character $character, array $buildingUpgradeRequest): array {
        if (ResourceValidation::shouldRedirectRebuildKingdomBuilding($building, $kingdom)) {
            $missingResources = ResourceValidation::getMissingCosts($building, $kingdom);

            $canAffordPopulation = true;

            if ($missingResources['population'] > 0) {
                $canAffordPopulation = $this->canAffordPopulationCost($kingdom, $missingResources['population']);
            }

            if (!$canAffordPopulation) {
                $this->messages[] = $building->name . ' has been rejected for reason of: Cannot afford to use: ' .
                    $kingdom->name . '\'s treasury to purchase an extra: ' .
                    $missingResources['population'] . ' population.';
            }

            $buildingUpgradeRequest['missing_costs'] = $missingResources;

            if ($canAffordPopulation) {
                $this->processResourceRequests($capitalCityBuildingQueue, $kingdom, $character, $building, $missingResources);
            }


            $buildingUpgradeRequest['secondary_status'] = $canAffordPopulation ? CapitalCityQueueStatus::REQUESTING : CapitalCityQueueStatus::REJECTED;

            return $buildingUpgradeRequest;
        }

        $buildingUpgradeRequest['secondary_status'] = CapitalCityQueueStatus::BUILDING;

        return $buildingUpgradeRequest;
    }


    private function processResourceRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue, Kingdom $kingdom, Character $character, KingdomBuilding $building, array $missingResources): void {
        foreach ($missingResources as $key => $amount) {
            $this->sendOffResourceRequests($capitalCityBuildingQueue, $kingdom, $character, $building, $key, $amount);
        }
    }

    private function sendOffResourceRequests(CapitalCityBuildingQueue $queue, Kingdom $kingdom, Character $character, KingdomBuilding $building, string $resourceName, int $resourceAmount): void {

        $kingdom = $character->kingdoms()->where('id', '!=', $kingdom->id)->where('current_' . $resourceName, '>=', $resourceAmount)->first();

        if (is_null($kingdom)) {

            $this->messages[] = 'No kingdom found to request resources from for: ' . $building->name;

            return;
        }

        $result = $this->resourceTransferService->sendOffResourceRequest($character, [
            'kingdom_requesting' => $kingdom->id,
            'kingdom_requesting_from' => $kingdom->id,
            'amount_of_resources' => $resourceAmount,
            'use_air_ship' => true,
            'type_of_resource' => $resourceName,
        ], $queue->id, $building->id);

        if ($result['status'] !== 200) {

            $this->messages[] = $result['message'];

            return;
        }

        $this->messages[] = 'Requesting resources for: ' . $building->name;
    }
}
