<?php

namespace App\Game\Kingdoms\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Requests\KingdomUnitRecruitmentRequest;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Requests\CancelUnitRequest;

class KingdomUnitsController extends Controller {

    /**
     * @var UpdateKingdom $updateKingdom
     */
    private UpdateKingdom $updateKingdom;

    /**
     * @var UnitService $unitService
     */
    private UnitService $unitService;

    /**
     * @param UpdateKingdom $updateKingdom
     * @param UnitService $unitService
     */
    public function __construct(UpdateKingdom $updateKingdom, UnitService $unitService) {

        $this->updateKingdom    = $updateKingdom;
        $this->unitService      = $unitService;
    }

    /**
     * @param KingdomUnitRecruitmentRequest $request
     * @param Kingdom $kingdom
     * @param GameUnit $gameUnit
     * @return JsonResponse
     * @throws \Exception
     */
    public function recruitUnits(KingdomUnitRecruitmentRequest $request, Kingdom $kingdom, GameUnit $gameUnit): JsonResponse {
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

        $amount = $request->amount < 1 ? 1 : $request->amount;

        $response = $this->unitService->handlePayment($gameUnit, $kingdom, $request->recruitment_type, $amount);

        if (!empty($response)) {
            return response()->json([
                'message' => $response['message']
            ], 422);
        }

        $this->unitService->recruitUnits($kingdom, $gameUnit, $amount, $this->unitService->getPaidGold());

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return response()->json([
            'message' => 'Your units are being trained by the best of the best!',
        ]);
    }

    /**
     * @param CancelUnitRequest $request
     * @return JsonResponse
     */
    public function cancelRecruit(CancelUnitRequest $request): JsonResponse {

        $queue = UnitInQueue::find($request->queue_id);
        $user = auth()->user();


        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        if ($queue->character_id !== $user->character->id) {
            return response()->json(['message' => 'Not allowed to do that.'], 422);
        }

        $kingdom = $this->unitService->cancelRecruit($queue);

        if (is_null($kingdom)) {
            return response()->json([
                'message' => 'Your units are almost done. You can\'t cancel this late in the process.'
            ], 422);
        }

        $this->updateKingdom->updateKingdom($kingdom);

        return response()->json([
            'message' => 'Your units have been disbanded. You got a % of some of the cost back in either resources or gold.'
        ]);
    }
}
