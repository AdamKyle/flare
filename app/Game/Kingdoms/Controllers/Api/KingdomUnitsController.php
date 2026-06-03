<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\GameUnit;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Kingdoms\Requests\CancelUnitRequest;
use App\Game\Kingdoms\Requests\KingdomUnitRecruitmentRequest;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class KingdomUnitsController extends Controller
{
    private UpdateKingdom $updateKingdom;

    private UnitService $unitService;

    public function __construct(
        UpdateKingdom $updateKingdom,
        UnitService $unitService,
        private readonly AutomationRestrictionService $automationRestrictionService
    ) {

        $this->updateKingdom = $updateKingdom;
        $this->unitService = $unitService;
    }

    /**
     * @throws \Exception
     */
    public function recruitUnits(KingdomUnitRecruitmentRequest $request, Kingdom $kingdom, GameUnit $gameUnit): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($kingdom->character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $hasActiveManualQueue = UnitInQueue::where('kingdom_id', $kingdom->id)
            ->where('game_unit_id', $gameUnit->id)
            ->where('completed_at', '>', now())
            ->exists();

        $hasActiveCapitalCityQueue = CapitalCityUnitQueue::where('kingdom_id', $kingdom->id)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::CANCELLED,
                CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ])
            ->get()
            ->contains(function (CapitalCityUnitQueue $queue) use ($gameUnit) {
                return collect($queue->unit_request_data)
                    ->reject(function (array $request) {
                        return in_array($request['secondary_status'] ?? null, [
                            CapitalCityQueueStatus::FINISHED,
                            CapitalCityQueueStatus::REJECTED,
                            CapitalCityQueueStatus::CANCELLED,
                            CapitalCityQueueStatus::CANCELLATION_REJECTED,
                        ], true);
                    })
                    ->contains(fn (array $request) => ($request['name'] ?? null) === $gameUnit->name);
            });

        if ($hasActiveManualQueue || $hasActiveCapitalCityQueue) {
            return response()->json([
                'message' => 'Unit is already in the process of recruiting.',
            ], 422);
        }

        if ($request->amount > KingdomMaxValue::MAX_UNIT) {
            return response()->json([
                'message' => 'Too many units',
            ], 422);
        }

        if ($request->amount <= 0) {
            return response()->json([
                'message' => 'Too few units to recruit.',
            ], 422);
        }

        $amount = $request->amount < 1 ? 1 : $request->amount;

        $response = $this->unitService->handlePayment($gameUnit, $kingdom, $amount);

        if (! empty($response)) {
            return response()->json([
                'message' => $response['message'],
            ], 422);
        }

        $this->unitService->recruitUnits($kingdom, $gameUnit, $amount);

        $this->updateKingdom->updateKingdom($kingdom->refresh());

        return response()->json([
            'message' => 'Your units are being trained by the best of the best! Check the queue tab to see how long until a unit is done.',
        ]);
    }

    public function cancelRecruit(CancelUnitRequest $request): JsonResponse
    {

        $queue = UnitInQueue::find($request->queue_id);
        $user = auth()->user();

        $restriction = $this->automationRestrictionJsonResponse($user->character);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if (is_null($queue)) {
            return response()->json(['message' => 'Invalid Input.'], 422);
        }

        if ($queue->character_id !== $user->character->id) {
            return response()->json(['message' => 'Not allowed to do that.'], 422);
        }

        if (! is_null($queue->capital_city_unit_queue_id)) {
            return response()->json(['message' => 'This queue is managed by your capital city. Cancel it from capital city management.'], 422);
        }

        $kingdom = $this->unitService->cancelRecruit($queue);

        if (is_null($kingdom)) {
            return response()->json([
                'message' => 'Your units are almost done. You can\'t cancel this late in the process.',
            ], 422);
        }

        $this->updateKingdom->updateKingdom($kingdom);

        return response()->json([
            'message' => 'Your units have been disbanded. You got a % of some of the cost back in either resources or gold.',
        ]);
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
