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

class KingdomUnitsController extends Controller
{

    /**
     * @var UpdateKingdomHandler $updateKingdomHandler
     */
    private UpdateKingdomHandler $updateKingdomHandler;

    /**
     * @var UnitService $unitService
     */
    private UnitService $unitService;

    /**
     * @param UpdateKingdomHandler $updateKingdomHandler
     * @param UnitService $unitService
     */
    public function __construct(UpdateKingdomHandler $updateKingdomHandler, UnitService $unitService) {

        $this->updateKingdomHandler    = $updateKingdomHandler;
        $this->unitService             = $unitService;
    }

    /**
     * @param KingdomUnitRecruitmentRequest $request
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @return JsonResponse
     * @throws \Exception
     */
    public function recruitUnits(KingdomUnitRecruitmentRequest $request, Kingdom $kingdom, GameUnit $gameUnit): JsonResponse
    {
        if ($request->amount > KingdomMaxValue::MAX_UNIT) {
            return response()->json([
                'message' => 'Too many units'
            ], 422);
        }

        if ($request->amount <= 0) {
            return response()->json([
                'message' => 'Too few units to recruit.'
            ], 422);
        }

        $paidGold = false;

        $response = $this->unitService->handlePayment($gameUnit, $kingdom, $request->recruitment_type, $request->amount);

        if (!empty($response)) {
            return response()->json([
                'message' => $response['message']
            ], 422);
        }

        $this->unitService->recruitUnits($kingdom, $gameUnit, $request->amount, $paidGold);

        $character = $kingdom->character;

        $this->updateKingdomHandler->refreshPlayersKingdoms($character->refresh());

        return response()->json([
            'message' => 'Your units are being trained by the best of the best!',
        ]);
    }

    /**
     * @param CancelUnitRequest $request
     * @return JsonResponse
     */
    public function cancelRecruit(CancelUnitRequest $request): JsonResponse
    {

        $queue = UnitInQueue::find($request->queue_id);

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        $cancelled = $this->unitService->cancelRecruit($queue);

        if (!$cancelled) {
            return response()->json([
                'message' => 'Your units are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        return response()->json([
            'message' => 'Your units have been disbanded. You got a % of some of the cost back in either resources or gold.'
        ]);
    }
}
