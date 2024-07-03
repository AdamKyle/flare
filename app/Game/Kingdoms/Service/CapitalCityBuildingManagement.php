<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomLog;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateBuildingUpgrades;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingUpgrades;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
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

            $time          = $this->unitMovementService->determineTimeRequired($character, $toKingdom, $kingdomId, PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION);

            $minutes       = now()->addMinutes($time);

            $queueData = [
                'kingdom_id' => $kingdomId,
                'requested_kingdom' => $kingdom->id,
                'building_request_data' => [],
            ];

            foreach ($buildings as $building) {
                $fromLevel = null;
                $toLevel = null;

                if ($type === 'upgrade') {
                    $fromLevel = $building->level;
                    $toLevel = $building->level + 1;
                }

                $queueData['building_request_data'][] = [
                    'building_id'   => $building->id,
                    'costs'         => $this->kingdomBuildingService->getBuildingCosts($building),
                    'type'          => $type,
                    'missing_costs' => [],
                    'secondary_status' => null,
                    'from_level' => $fromLevel,
                    'to_level' => $toLevel,
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

        event(new UpdateCapitalCityBuildingQueueTable($character, $kingdom));

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

            $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

            $requestData[$index] = $buildingUpgradeRequest;

            $capitalCityBuildingQueue->update([
                'building_request_data' => $requestData,
            ]);

            $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

            if ($buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::REQUESTING) {
                continue;
            }

            // If the request is ready for building, handle it immediately
            if ($buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::BUILDING ||
                $buildingUpgradeRequest['secondary_status'] === CapitalCityQueueStatus::REPAIRING)
            {
                $result = $this->handleBuildingRequest($capitalCityBuildingQueue, $building, $character);

                if (!$result) {
                    $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                }
            }
        }

        $capitalCityBuildingQueue->update([
            'building_request_data' => $requestData,
            'messages' => $this->messages,
        ]);

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        event(new UpdateCapitalCityBuildingQueueTable($capitalCityBuildingQueue->character));

        $this->possiblyCreateLogForQueue($capitalCityBuildingQueue);
    }

    /**
     * Send a log if all the buildings are done or rejected (or both)
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @return void
     */
    public function possiblyCreateLogForQueue(CapitalCityBuildingQueue $capitalCityBuildingQueue): void {

        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        $buildingData = [];

        foreach($requestData as $data) {
            if ($data['secondary_status'] === CapitalCityQueueStatus::REJECTED ||
                $data['secondary_status'] === CapitalCityQueueStatus::FINISHED
            ) {

                $building = KingdomBuilding::where('kingdom_id', $kingdom->id)->where('id', $data['building_id'])->first();

                $buildingData[] = [
                    'building_name' => $building->name,
                    'from_level' => $data['from_level'],
                    'to_level' => $data['to_level'],
                    'type'  => $data['type'],
                    'status' => $data['secondary_status'],
                ];
            }
        }

        if (count($buildingData) === count($requestData)) {
            KingdomLog::create([
                'character_id' => $character->id,
                'from_kingdom_id' => $capitalCityBuildingQueue->requested_kingdom,
                'to_kingdom_id' => $kingdom->id,
                'opened' => false,
                'additional_details' => [
                    'messages' => $capitalCityBuildingQueue->messages,
                    'building_data' => $buildingData,
                ],
                'status' => KingdomLogStatusValue::CAPITAL_CITY_BUILDING_REQUEST,
                'published' => true,
            ]);

            $this->updateKingdom->updateKingdomLogs($kingdom->character, true);

            $capitalCityBuildingQueue->delete();

            event(new UpdateCapitalCityBuildingQueueTable($character));
        }
    }

    /**
     * Handle the actual building request.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param KingdomBuilding $building
     * @param Character $character
     * @return bool
     */
    public function handleBuildingRequest(CapitalCityBuildingQueue $capitalCityBuildingQueue, KingdomBuilding $building, Character $character): bool {
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $buildingData = $capitalCityBuildingQueue->building_request_data;

        if ($this->needsPopulationCost($building, $buildingData)) {
            $population = $building->missing_costs['population'];
            $cost = (new UnitCosts(UnitCosts::PERSON))->fetchCost() * $population;
            $treasury = $kingdom->treasury;

            $treasury -= $cost;

            $kingdom->update([
                'treasury' => $treasury,
            ]);
        }

        $kingdom = $this->kingdomBuildingService->updateKingdomResourcesForRebuildKingdomBuilding($building);

        if ($this->isRepairRequest($building, $buildingData)) {
            $this->kingdomBuildingService->rebuildKingdomBuilding($building, $character, $capitalCityBuildingQueue->id);
        } else {

            if ($building->is_locked) {

                $this->messages[] = 'Building is locked and cannot be upgraded for: ' . $kingdom->name . '.';

                return false;
            }

            if ($building->current_level >= $building->gameBuilding->max_level) {
                $this->messages[] = 'Building is already max level and cannot be upgraded for: ' . $kingdom->name . '.';

                return false;
            }

            $this->kingdomBuildingService->upgradeKingdomBuilding($building, $character, $capitalCityBuildingQueue->id);
        }

        $this->updateKingdom->updateKingdom($kingdom);

        return true;
    }

    /**
     * Do we need to handle the population cost?
     *
     * @param KingdomBuilding $building
     * @param array $buildingData
     * @return bool
     */
    private function needsPopulationCost(KingdomBuilding $building, array $buildingData): bool {

        foreach ($buildingData as $requestData) {
            if ($requestData['building_id'] === $building->id) {
                return isset($requestData['missing_costs']['population']) && $requestData['missing_costs']['population'] > 0;
            }
        }

        return false;
    }

    /**
     * Are we a repair request?
     *
     * @param KingdomBuilding $building
     * @param array $buildingData
     * @return bool
     */
    private function isRepairRequest(KingdomBuilding $building, array $buildingData): bool {

        foreach ($buildingData as $requestData) {
            if ($requestData['building_id'] === $building->id) {
                return $requestData['type'] === 'repair';
            }
        }

        return false;
    }

    /**
     * Can we afford, kingdom treasury, the population cost?
     *
     * @param Kingdom $kingdom
     * @param int $populationAmount
     * @return bool
     */
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

    /**
     * If we need to request resources, lets send that off.
     *
     * If we do not need resources, we mark as either rejected or ready to build.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Kingdom $kingdom
     * @param KingdomBuilding $building
     * @param Character $character
     * @param array $buildingUpgradeRequest
     * @return array
     */
    private function processPotentialResourceRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue, Kingdom $kingdom, KingdomBuilding $building, Character $character, array $buildingUpgradeRequest): array {
        if (ResourceValidation::shouldRedirectRebuildKingdomBuilding($building, $kingdom)) {
            $missingResources = ResourceValidation::getMissingCosts($building, $kingdom);

            $canAffordPopulation = false;

            if ($missingResources['population'] > 0) {
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

            $processResult = true;

            if ($canAffordPopulation) {
                $processResult = $this->processResourceRequests($capitalCityBuildingQueue, $kingdom, $character, $building, $missingResources);
            }


            $buildingUpgradeRequest['secondary_status'] = ($canAffordPopulation && $processResult) ? CapitalCityQueueStatus::REQUESTING : CapitalCityQueueStatus::REJECTED;

            return $buildingUpgradeRequest;
        }

        $buildingUpgradeRequest['secondary_status'] = $buildingUpgradeRequest['type'] === 'repair' ? CapitalCityQueueStatus::REPAIRING : CapitalCityQueueStatus::BUILDING;

        return $buildingUpgradeRequest;
    }

    /**
     * Process sending off resource requests.
     *
     * @param CapitalCityBuildingQueue $capitalCityBuildingQueue
     * @param Kingdom $kingdom
     * @param Character $character
     * @param KingdomBuilding $building
     * @param array $missingResources
     * @return bool
     */
    private function processResourceRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue, Kingdom $kingdom, Character $character, KingdomBuilding $building, array $missingResources): bool {
        foreach ($missingResources as $key => $amount) {
            $result = $this->sendOffResourceRequests($capitalCityBuildingQueue, $kingdom, $character, $building, $key, $amount);

            if (!$result) {

                $buildingQueues = $capitalCityBuildingQueue->building_request_data;

                foreach ($buildingQueues as $index => $queueData) {
                    if ($queueData['building_id'] === $building->id) {
                        $buildingQueues[$index]['secondary_status'] = CapitalCityQueueStatus::REJECTED;
                    }
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Send off the resource request based on what we need. This can and will send multiple requests.
     *
     * Returns true or false if the request was sent.
     *
     * @param CapitalCityBuildingQueue $queue
     * @param Kingdom $kingdom
     * @param Character $character
     * @param KingdomBuilding $building
     * @param string $resourceName
     * @param int $resourceAmount
     * @return bool
     */
    private function sendOffResourceRequests(CapitalCityBuildingQueue $queue, Kingdom $kingdom, Character $character, KingdomBuilding $building, string $resourceName, int $resourceAmount): bool {

        $kingdom = $character->kingdoms()->where('id', '!=', $kingdom->id)->where('current_' . $resourceName, '>=', $resourceAmount)->first();

        if (is_null($kingdom)) {

            $this->messages[] = 'No kingdom found to request resources from for: ' . $building->name;

            return false;
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

            return false;
        }

        return true;
    }
}
