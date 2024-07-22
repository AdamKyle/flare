<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\BuildingUpgradeRequestsRequest;
use App\Game\Kingdoms\Requests\CancelBuildingUpgradeRequestRequest;
use App\Game\Kingdoms\Requests\CancelUnitRequestRequest;
use App\Game\Kingdoms\Requests\RecruitUnitCancellationRequest;
use App\Game\Kingdoms\Requests\RecruitUnitRequestsRequest;
use App\Game\Kingdoms\Service\CancelBuildingRequestService;
use App\Game\Kingdoms\Service\CancelUnitRequestService;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CapitalCityManagementController extends Controller {

    public function __construct(private readonly CapitalCityManagementService $capitalCityManagementService,
                                private readonly CancelBuildingRequestService $cancelBuildingRequestService,
                                private readonly CancelUnitRequestService $cancelUnitRequestService
    ) {}

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
        $result = $this->capitalCityManagementService->fetchKingdomsForSelection($kingdom);

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
        $result = $this->capitalCityManagementService->sendoffBuildingRequests($character, $kingdom, $buildingUpgradeRequestsRequest->request_data, $buildingUpgradeRequestsRequest->request_type);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchKingdomBuildingManagementQueues(Character $character, Kingdom $kingdom) {
        $data = $this->capitalCityManagementService->fetchBuildingQueueData($character, $kingdom);

        return response()->json([
            'building_queues' => $data,
        ]);
    }

    public function fetchKingdomUnitManagementQueues(Character $character, Kingdom $kingdom) {
        $data = $this->capitalCityManagementService->fetchUnitQueueData($character, $kingdom);

        return response()->json([
            'unit_queues' => $data,
        ]);
    }

    public function recruitUnits(RecruitUnitRequestsRequest $recruitUnitRequestsRequest, Character $character, Kingdom $kingdom) {
        $result = $this->capitalCityManagementService->sendOffUnitRecruitmentOrders($character, $kingdom, $recruitUnitRequestsRequest->request_data);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function cancelUnitRecruitOrders(RecruitUnitCancellationRequest $request, Character $character, Kingdom $kingdom) {
        $result = $this->cancelUnitRequestService->handleCancelRequest($character, $kingdom, $request->all()['request_data']);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function cancelBuildingOrdersOrders(CancelUnitRequestRequest $request, Character $character, Kingdom $kingdom) {
        $result = $this->cancelBuildingRequestService->handleCancelRequest($character, $kingdom, $request->all()['request_data']);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}