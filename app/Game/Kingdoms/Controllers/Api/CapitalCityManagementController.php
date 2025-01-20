<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Requests\BuildingUpgradeRequestsRequest;
use App\Game\Kingdoms\Requests\CapitalCityCancelBuildingRequest;
use App\Game\Kingdoms\Requests\PurchaseGoldBarsRequest;
use App\Game\Kingdoms\Requests\RecruitUnitCancellationRequest;
use App\Game\Kingdoms\Requests\RecruitUnitRequestsRequest;
use App\Game\Kingdoms\Requests\WithdrawGoldBarsRequest;
use App\Game\Kingdoms\Service\CancelBuildingRequestService;
use App\Game\Kingdoms\Service\CancelUnitRequestService;
use App\Game\Kingdoms\Service\CapitalCityGoldBarManagementService;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CapitalCityManagementController extends Controller
{
    public function __construct(
        private readonly CapitalCityManagementService $capitalCityManagementService,
        private readonly CancelBuildingRequestService $cancelBuildingRequestService,
        private readonly CancelUnitRequestService $cancelUnitRequestService,
        private readonly CapitalCityGoldBarManagementService $capitalCityGoldBarManagementService,
    ) {}

    public function makeCapitalCity(Kingdom $kingdom, Character $character): JsonResponse
    {
        $result = $this->capitalCityManagementService->makeCapitalCity($kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchKingdomsWithUpgradableBuildingType(Character $character, Kingdom $kingdom)
    {
        $result = $this->capitalCityManagementService->fetchBuildingsForUpgradesOrRepairs($character, $kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchKingdomsWithRecruitableUnitType(Character $character, Kingdom $kingdom)
    {
        $result = $this->capitalCityManagementService->fetchKingdomsForSelection($kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function walkAllKingdoms(Character $character, Kingdom $kingdom)
    {
        $result = $this->capitalCityManagementService->walkAllKingdoms($character, $kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function upgradeBuildings(BuildingUpgradeRequestsRequest $buildingUpgradeRequestsRequest, Character $character, Kingdom $kingdom)
    {
        Log::channel('capital_city_building_upgrades')->info('upgradeBuildings endpoint called', [
            '$buildingUpgradeRequestsRequest' => $buildingUpgradeRequestsRequest->all(),
            '$character' => $character->id,
            '$kingdom' => $kingdom->id,
        ]);

        $result = $this->capitalCityManagementService->sendoffBuildingRequests($character, $kingdom, $buildingUpgradeRequestsRequest->request_data, $buildingUpgradeRequestsRequest->request_type);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchKingdomBuildingManagementQueues(Character $character, Kingdom $kingdom)
    {
        $data = $this->capitalCityManagementService->fetchBuildingQueueData($character, $kingdom);

        return response()->json([
            'building_queues' => $data,
        ]);
    }

    public function fetchKingdomUnitManagementQueues(Character $character, Kingdom $kingdom)
    {
        $data = $this->capitalCityManagementService->fetchUnitQueueData($character, $kingdom);

        return response()->json([
            'unit_queues' => $data,
        ]);
    }

    public function recruitUnits(RecruitUnitRequestsRequest $recruitUnitRequestsRequest, Character $character, Kingdom $kingdom)
    {
        $result = $this->capitalCityManagementService->sendOffUnitRecruitmentOrders($character, $kingdom, $recruitUnitRequestsRequest->request_data);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function cancelUnitRecruitOrders(RecruitUnitCancellationRequest $request, Character $character, Kingdom $kingdom)
    {
        $result = $this->cancelUnitRequestService->handleCancelRequest($character, $kingdom, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function cancelBuildingOrdersOrders(CapitalCityCancelBuildingRequest $request, Character $character, Kingdom $kingdom)
    {
        $result = $this->cancelBuildingRequestService->handleCancelRequest($character, $kingdom, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function fetchGoldBarData(Character $character, Kingdom $kingdom)
    {
        $result = $this->capitalCityGoldBarManagementService->fetchGoldBarDetails($character, $kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function withDrawGoldBars(WithdrawGoldBarsRequest $request, Character $character, Kingdom $kingdom)
    {
        $result = $this->capitalCityGoldBarManagementService->convertGoldBars($character, $kingdom, $request->amount_to_withdraw);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function depositGoldBars(PurchaseGoldBarsRequest $request, Character $character, Kingdom $kingdom)
    {
        $result = $this->capitalCityGoldBarManagementService->depositGoldBars($character, $kingdom, $request->amount_to_purchase);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
