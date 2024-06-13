<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
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

    }

    public function walkAllKingdoms(Character $character, Kingdom $kingdom) {

    }

    public function upgradeBuildings(Character $character, Kingdom $kingdom) {

    }

    public function recruitUnits(Character $character, Kingdom $kingdom) {

    }
}
