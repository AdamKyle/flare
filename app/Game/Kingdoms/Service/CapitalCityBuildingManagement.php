<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateBuildingUpgrades;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use App\Game\Kingdoms\Validation\ResourceValidation;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;

class CapitalCityBuildingManagement {

    use ResponseBuilder;

    private array $messages = [];

    public function __construct(private readonly KingdomBuildingService $kingdomBuildingService,
                                private readonly UnitMovementService $unitMovementService,
                                private readonly ResourceTransferService $resourceTransferService) {}

    public function createBuildingUpgradeRequestQueue(Character $character, Kingdom $kingdom, array $requests, string $type): array {

        foreach ($requests as $request) {

            $kingdomId = $request['kingdom_id'];
            $buildingIds = $request['building_ids'];

            $buildings = KingdomBuilding::where('kingdom_id', $kingdomId)->whereIn('id', $buildingIds)->get();

            $toKingdom = Kingdom::find($kingdomId);

            $time          = $this->unitMovementService->determineTimeRequired($character, $toKingdom, $kingdomId);

            $minutes       = now()->addMinutes($time);

            $queueData = [
                'kingdom_id' => $request['kingdom_id'],
                'building_upgrade_requests' => [],
            ];

            foreach ($buildings as $building) {
                $queueData['building_upgrade_requests'][] = [
                    'building_id'   => $building->id,
                    'costs'         => $this->kingdomBuildingService->getBuildingCosts($building),
                    'type'          => $type,
                    'missing_costs' => [],
                    'secondary_status' => null,
                ];
            }

            $delayTime  = now()->addMinutes($minutes);

            $queueData['status'] = CapitalCityQueueStatus::TRAVELING;
            $queueData['started_at'] = now();
            $queueData['completed_at'] = $delayTime;

            $capitalCityBuildingQueue = CapitalCityBuildingQueue::create($queueData);

            CapitalCityBuildingRequestMovement::dispatch($capitalCityBuildingQueue->id, $character->id)->delay($delayTime);
        }

        event(new UpdateBuildingUpgrades($character, $kingdom));

        return $this->successResult([
            'message' => 'Building upgrades have been sent off to their respective kingdoms.
            The list below has been updated to reflect kingdoms you can send upgrade requests to. If
            you click: "Building Upgrade/Repair" in the top right, you will see a table of orders and
            their associated statuses.'
        ]);
    }

    public function processBuildingRequest(CapitalCityBuildingQueue $capitalCityBuildingQueue): void {

        $requestData = $capitalCityBuildingQueue->building_request_data;
        $kingdom = $capitalCityBuildingQueue->kingdom;
        $character = $capitalCityBuildingQueue->character;

        foreach ($requestData as $index => $buildingUpgradeRequest) {

            $building = $kingdom->buildings()->where('id', $buildingUpgradeRequest['building_id'])->first();

            if (ResourceValidation::shouldRedirectRebuildKingdomBuilding($building, $kingdom)) {
                $missingResources = ResourceValidation::getMissingCosts($building, $kingdom);

                $requestData[$index]['missing_costs'] = $missingResources;

                $this->processResourceRequests($capitalCityBuildingQueue, $kingdom, $character, $missingResources, $building->name);

                $requestData[$index]['secondary_status'] = CapitalCityQueueStatus::REQUESTING;
            }

        }

        $capitalCityBuildingQueue->update([
            'building_queue_data' => $requestData,
            'messages' => $this->messages,
        ]);
    }

    private function processResourceRequests(CapitalCityBuildingQueue $capitalCityBuildingQueue, Kingdom $kingdom, Character $character, array $missingResources, string $buildingName): void {
        foreach ($missingResources as $key => $amount) {
            $this->sendOffResourceRequests($capitalCityBuildingQueue, $kingdom, $character, $buildingName, $key, $amount);
        }
    }

    private function sendOffResourceRequests(CapitalCityBuildingQueue $queue, Kingdom $kingdom, Character $character, string $buildingName, string $resourceName, int $resourceAmount): void {

        $kingdom = $character->kingdoms()->where('kingdom_id', '!=', $kingdom->id)->where('current_' . $resourceName, '>=', $resourceAmount)->first();

        if (is_null($kingdom)) {

            $this->messages[] = 'No kingdom found to request resources from for: ' . $buildingName;

            return;
        }

        $result = $this->resourceTransferService->sendOffResourceRequest($character, [
            'kingdom_requesting' => $kingdom->id,
            'kingdom_requesting_from' => $kingdom->id,
            'amount_of_resources' => $resourceAmount,
            'use_air_ship' => true,
            'type_of_resource' => $resourceName,
        ]);

        if ($result['status'] !== 200) {

            $this->messages[] = $result['message'];

            return;
        }

        $this->messages[] = 'Requesting resources for: ' . $buildingName;
    }
}
