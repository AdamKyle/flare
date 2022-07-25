<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\UnitMovementQueue;
use App\Flare\Transformers\BasicKingdomTransformer;
use App\Flare\Transformers\OtherKingdomTransformer;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Requests\CancelBuildingRequest;
use App\Game\Kingdoms\Requests\CancelUnitRequest;
use App\Game\Kingdoms\Requests\KingdomUpgradeBuildingRequest;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Game\Messages\Events\GlobalMessageEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Facades\App\Game\Kingdoms\Validation\ResourceValidation;
use App\Http\Controllers\Controller;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Flare\Models\User;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Jobs\CoreJob;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Kingdoms\Jobs\MassEmbezzle;
use App\Game\Kingdoms\Requests\KingdomDepositRequest;
use App\Game\Kingdoms\Requests\KingdomUnitRecruitmentRequest;
use App\Game\Kingdoms\Requests\PurchaseGoldBarsRequest;
use App\Game\Kingdoms\Requests\PurchasePeopleRequest;
use App\Game\Kingdoms\Requests\WithdrawGoldBarsRequest;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitCosts;
use App\Game\Kingdoms\Requests\KingdomRenameRequest;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Kingdoms\Requests\KingdomEmbezzleRequest;
use App\Game\Kingdoms\Events\AddKingdomToMap;
use App\Game\Kingdoms\Events\UpdateGlobalMap;
use App\Game\Messages\Events\ServerMessageEvent;

class KingdomBuildingsController extends Controller
{

    /**
     * @var UpdateKingdomHandler $updateKingdomHandler
     */
    private UpdateKingdomHandler $updateKingdomHandler;

    /**
     * @var KingdomBuildingService $kingdomBuildingService
     */
    private KingdomBuildingService $kingdomBuildingService;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     * @param KingdomBuildingService $kingdomBuildingService
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler, KingdomBuildingService $kingdomBuildingService) {
        $this->updateKingdomHandler    = $updateKingdomHandler;
        $this->kingdomBuildingService  = $kingdomBuildingService;
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

        $this->updateKingdomHandler->refreshPlayersKingdoms($character->refresh());

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

        $this->updateKingdomHandler->refreshPlayersKingdoms($character->refresh());

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

        $canceled = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue);

        if (!$canceled) {
            return response()->json([
                'message' => 'Your workers are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        return response()->json([
            'message' => 'Building has been removed from queue. Some resources or gold was given back to you based on percentage of time left.'
        ], 200);
    }
}
