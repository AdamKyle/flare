<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Requests\CancelBuildingRequest;
use App\Game\Kingdoms\Requests\KingdomUpgradeBuildingRequest;
use Illuminate\Http\JsonResponse;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use App\Http\Controllers\Controller;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Service\KingdomBuildingService;

class KingdomBuildingsController extends Controller {

    /**
     * @var UpdateKingdom $updateKingdomsService
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @var KingdomBuildingService $kingdomBuildingService
     */
    private KingdomBuildingService $kingdomBuildingService;

    /**
     * @param UpdateKingdom $updateKingdom
     * @param KingdomBuildingService $kingdomBuildingService
     */
    public function __construct(UpdateKingdom $updateKingdom, KingdomBuildingService $kingdomBuildingService) {
        $this->updateKingdom          = $updateKingdom;
        $this->kingdomBuildingService = $kingdomBuildingService;
    }

    /**
     * @param KingdomUpgradeBuildingRequest $request
     * @param Character $character
     * @param KingdomBuilding $building
     * @return JsonResponse
     */
    public function upgradeKingdomBuilding(KingdomUpgradeBuildingRequest $request, Character $character, KingdomBuilding $building): JsonResponse {
        if ($request->paying_with_gold) {
            $paid = $this->kingdomBuildingService->upgradeBuildingWithGold($building, $request->all());

            if (!$paid) {
                return response()->json([
                    'message' => 'You cannot afford this upgrade.'
                ], 422);
            }

            $this->kingdomBuildingService->processUpgradeWithGold($building, $paid, $request->to_level);
        } else {
            if (ResourceValidation::shouldRedirectKingdomBuilding($building, $building->kingdom)) {
                return response()->json([
                    'message' => "You don't have the resources."
                ], 422);
            }

            if ($building->level + 1 > $building->gameBuilding->max_level) {
                return response()->json([
                    'message' => 'Building is already max level.'
                ], 422);
            }

            $this->kingdomBuildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);

            $this->kingdomBuildingService->upgradeKingdomBuilding($building, $character);
        }

        $this->updateKingdom->updateKingdom($building->kingdom->refresh());

        return response()->json([
            'message' => 'Building is in the process of upgrading!',
        ], 200);
    }

    /**
     * @param Character $character
     * @param KingdomBuilding $building
     * @return JsonResponse
     */
    public function rebuildKingdomBuilding(Character $character, KingdomBuilding $building): JsonResponse {
        if (ResourceValidation::shouldRedirectRebuildKingdomBuilding($building, $building->kingdom)) {
            return response()->json([
                'message' => "You don't have the resources."
            ], 422);
        }

        $kingdom = $this->kingdomBuildingService->updateKingdomResourcesForRebuildKingdomBuilding($building);

        $this->kingdomBuildingService->rebuildKingdomBuilding($building, $character);

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return response()->json([
            'Message' => 'Kingdom building is added to the queue to be rebuilt.'
        ], 200);
    }

    /**
     * @param CancelBuildingRequest $request
     * @return JsonResponse
     */
    public function removeKingdomBuildingFromQueue(CancelBuildingRequest $request): JsonResponse {

        $queue = BuildingInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        $building = $queue->building;

        $canceled = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue);

        if (!$canceled) {
            return response()->json([
                'message' => 'Your workers are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        $this->updateKingdom->updateKingdom($building->kingdom->refresh());

        return response()->json([
            'message' => 'Building has been removed from queue. Some resources or gold was given back to you based on percentage of time left.'
        ], 200);
    }
}
