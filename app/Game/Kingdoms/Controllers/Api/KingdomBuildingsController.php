<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\Character;
use App\Flare\Models\KingdomBuilding;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Kingdoms\Requests\CancelBuildingRequest;
use App\Game\Kingdoms\Requests\KingdomUpgradeBuildingRequest;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Http\Controllers\Controller;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use Illuminate\Http\JsonResponse;

class KingdomBuildingsController extends Controller
{
    private UpdateKingdom $updateKingdom;

    private KingdomBuildingService $kingdomBuildingService;

    public function __construct(
        UpdateKingdom $updateKingdom,
        KingdomBuildingService $kingdomBuildingService,
        private readonly AutomationRestrictionService $automationRestrictionService
    )
    {
        $this->updateKingdom = $updateKingdom;
        $this->kingdomBuildingService = $kingdomBuildingService;
    }

    public function upgradeKingdomBuilding(KingdomUpgradeBuildingRequest $request, Character $character, KingdomBuilding $building): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if ($this->kingdomBuildingService->hasActiveBuildingUpgrade($building)) {
            return response()->json([
                'message' => 'Building is already in the process of upgrading.',
            ], 422);
        }

        $fromLevel = $request->has('from_level') ? (int) $request->from_level : null;
        $toLevel = (int) $request->to_level;

        if ($this->kingdomBuildingService->isBuildingDamaged($building)) {
            return response()->json([
                'message' => 'Building must be repaired before it can be upgraded.',
            ], 422);
        }

        if ($this->kingdomBuildingService->cannotUpgradePastMaxLevel($building, $toLevel)) {
            return response()->json([
                'message' => 'Building is already max level.',
            ], 422);
        }

        if ($this->kingdomBuildingService->hasInvalidUpgradeLevels($building, $fromLevel, $toLevel)) {
            return response()->json([
                'message' => 'Invalid building upgrade request.',
            ], 422);
        }

        if (ResourceValidation::shouldRedirectKingdomBuilding($building, $building->kingdom)) {
            return response()->json([
                'message' => "You don't have the resources.",
            ], 422);
        }

        $this->kingdomBuildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);

        $this->kingdomBuildingService->upgradeKingdomBuilding($building, $character);

        $this->updateKingdom->updateKingdom($building->kingdom->refresh());

        return response()->json([
            'message' => 'Building is in the process of upgrading!',
        ], 200);
    }

    public function rebuildKingdomBuilding(Character $character, KingdomBuilding $building): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if ($this->kingdomBuildingService->hasActiveBuildingUpgrade($building)) {
            return response()->json([
                'message' => 'Building is already in the process of upgrading.',
            ], 422);
        }

        if (ResourceValidation::shouldRedirectKingdomBuilding($building, $building->kingdom)) {
            return response()->json([
                'message' => "You don't have the resources.",
            ], 422);
        }

        $kingdom = $this->kingdomBuildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);

        $this->kingdomBuildingService->rebuildKingdomBuilding($building, $character);

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return response()->json([
            'Message' => 'Kingdom building is added to the queue to be rebuilt.',
        ], 200);
    }

    public function removeKingdomBuildingFromQueue(CancelBuildingRequest $request): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse(auth()->user()->character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $queue = BuildingInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        if (! is_null($queue->capital_city_building_queue_id)) {
            return response()->json(['message' => 'This queue is managed by your capital city. Cancel it from capital city management.'], 422);
        }

        $building = $queue->building;

        $canceled = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue);

        if (! $canceled) {
            return response()->json([
                'message' => 'Your workers are almost done. You can\'t cancel this late in the process.',
            ], 422);
        }

        $this->updateKingdom->updateKingdom($building->kingdom->refresh());

        return response()->json([
            'message' => 'Building has been removed from queue. Some resources or gold was given back to you based on percentage of time left.',
        ], 200);
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
