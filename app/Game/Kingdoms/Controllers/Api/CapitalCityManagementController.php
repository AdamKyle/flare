<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpBuildingRequests;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpUnitRequests;
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
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
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
        private readonly AutomationRestrictionService $automationRestrictionService,
    ) {}

    public function makeCapitalCity(Kingdom $kingdom, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

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
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->capitalCityManagementService->walkAllKingdoms($character, $kingdom);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function upgradeBuildings(BuildingUpgradeRequestsRequest $buildingUpgradeRequestsRequest, Character $character, Kingdom $kingdom)
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        Log::channel('capital_city_building_upgrades')->info('upgradeBuildings endpoint called', [
            '$buildingUpgradeRequestsRequest' => $buildingUpgradeRequestsRequest->all(),
            '$character' => $character->id,
            '$kingdom' => $kingdom->id,
        ]);

        CapitalCityQueueUpBuildingRequests::dispatch($character->id, $kingdom->id, $buildingUpgradeRequestsRequest->request_data, $buildingUpgradeRequestsRequest->request_type)->onQueue('default_long')->delay(now()->addSecond());

        return response()->json([]);
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
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $requestedAmounts = [];

        foreach ($recruitUnitRequestsRequest->request_data as $kingdomRequestData) {
            $targetKingdom = $character->kingdoms()->find($kingdomRequestData['kingdom_id'] ?? null);

            if (is_null($targetKingdom)) {
                continue;
            }

            foreach ($kingdomRequestData['unit_requests'] ?? [] as $unitRequest) {
                $gameUnit = GameUnit::where('name', $unitRequest['unit_name'] ?? null)->first();

                if (is_null($gameUnit)) {
                    continue;
                }

                $key = $targetKingdom->id . ':' . $gameUnit->id;
                $requestedAmounts[$key] = ($requestedAmounts[$key] ?? 0) + (int) ($unitRequest['unit_amount'] ?? 0);

                $ownedAmount = $targetKingdom->units()
                    ->where('game_unit_id', $gameUnit->id)
                    ->sum('amount');
                $activeManualQueueAmount = UnitInQueue::where('kingdom_id', $targetKingdom->id)
                    ->where('game_unit_id', $gameUnit->id)
                    ->where('completed_at', '>', now())
                    ->sum('amount');
                $activeCapitalCityQueueAmount = CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)
                    ->whereNotIn('status', [
                        CapitalCityQueueStatus::FINISHED,
                        CapitalCityQueueStatus::REJECTED,
                        CapitalCityQueueStatus::CANCELLED,
                        CapitalCityQueueStatus::CANCELLATION_REJECTED,
                    ])
                    ->get()
                    ->sum(function (CapitalCityUnitQueue $queue) use ($gameUnit) {
                        return collect($queue->unit_request_data)
                            ->reject(function (array $request) {
                                return in_array($request['secondary_status'] ?? null, [
                                    CapitalCityQueueStatus::FINISHED,
                                    CapitalCityQueueStatus::REJECTED,
                                    CapitalCityQueueStatus::CANCELLED,
                                    CapitalCityQueueStatus::CANCELLATION_REJECTED,
                                ], true);
                            })
                            ->where('name', $gameUnit->name)
                            ->sum('amount');
                    });

                if ($ownedAmount + $activeManualQueueAmount + $activeCapitalCityQueueAmount + $requestedAmounts[$key] > KingdomMaxValue::MAX_UNIT) {
                    return response()->json([
                        'message' => 'One or more unit requests exceed the maximum allowed units.',
                    ], 422);
                }
            }
        }

        Log::channel('capital_city_unit_recruitments')->info('recruitUnits endpoint called', [
            '$buildingUpgradeRequestsRequest' => $recruitUnitRequestsRequest->all(),
            '$character' => $character->id,
            '$kingdom' => $kingdom->id,
        ]);

        CapitalCityQueueUpUnitRequests::dispatch($character->id, $kingdom->id, $recruitUnitRequestsRequest->request_data)->onQueue('default_long')->delay(now()->addSecond());

        return response()->json([]);
    }

    public function cancelUnitRecruitOrders(RecruitUnitCancellationRequest $request, Character $character, Kingdom $kingdom)
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->cancelUnitRequestService->handleCancelRequest($character, $kingdom, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function cancelBuildingOrdersOrders(CapitalCityCancelBuildingRequest $request, Character $character, Kingdom $kingdom)
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

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

    private function automationRestrictionJsonResponse(Character $character): ?JsonResponse
    {
        $restriction = $this->automationRestrictionService->blockedContext($character, AutomationRestrictionService::KINGDOM_MANAGEMENT);

        if (is_null($restriction)) {
            return null;
        }

        return response()->json(['message' => $restriction['message']], 422);
    }
}
