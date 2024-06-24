<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomBuilding;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Kingdoms\Events\UpdateBuildingUpgrades;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;

class CapitalCityBuildingManagement {

    use ResponseBuilder;

    public function __construct(private readonly KingdomBuildingService $kingdomBuildingService, private readonly UnitMovementService $unitMovementService) {}

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
                    'building_id' => $building->id,
                    'costs'       => $this->kingdomBuildingService->getBuildingCosts($building),
                    'type'        => $type,
                ];
            }

            $delayTime  = now()->addMinutes($minutes);

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
}
