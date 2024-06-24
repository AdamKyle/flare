<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\BuildingUpgradeRequestsRequest;
use App\Game\Kingdoms\Requests\RecruitUnitRequestsRequest;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CapitalCityManagementController extends Controller {

    public function __construct(private readonly CapitalCityManagementService $capitalCityManagementService) {}

    public function makeCapitalCity(Kingdom $kingdom, Character $character): JsonResponse {
        $result = $this->capitalCityManagementService->makeCapitalCity($kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchKingdomsWithUpgradableBuildingType(Character $character, Kingdom $kingdom) {
        $result = $this->capitalCityManagementService->fetchBuildingsForUpgradesOrRepairs($character, $kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchKingdomsWithRecruitableUnitType(Character $character, Kingdom $kingdom) {
        $result = $this->capitalCityManagementService->fetchKingdomsForSelection($character, $kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function walkAllKingdoms(Character $character, Kingdom $kingdom) {
        $result = $this->capitalCityManagementService->walkAllKingdoms($character, $kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function upgradeBuildings(BuildingUpgradeRequestsRequest $buildingUpgradeRequestsRequest, Character $character, Kingdom $kingdom) {
        dump($buildingUpgradeRequestsRequest->all());

        $result = $this->capitalCityManagementService->sendoffBuildingRequests($character, $kingdom, $buildingUpgradeRequestsRequest->request_data, $buildingUpgradeRequestsRequest->request_type);
    }

    public function recruitUnits(RecruitUnitRequestsRequest $recruitUnitRequestsRequest, Character $character, Kingdom $kingdom) {
        dump($recruitUnitRequestsRequest->all());
    }
}
