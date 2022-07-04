<?php

namespace App\Game\Kingdoms\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;

class KingdomSettleController extends Controller {

    /**
     * @var KingdomSettleService $kingdomSettleService
     */
    private KingdomSettleService $kingdomSettleService;

    /**
     * @param KingdomSettleService $kingdomSettleService
     */
    public function __construct(KingdomSettleService $kingdomSettleService) {
        $this->kingdomSettleService    = $kingdomSettleService;
    }

    /**
     * @param KingdomsSettleRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function settle(KingdomsSettleRequest $request, Character $character): JsonResponse {

        $result = $this->kingdomSettleService->settlePreCheck($character, $request->name);

        if (!empty($result)) {
            return response()->json([
                'message' => $result['message']
            ], 422);
        }

        if (!$this->kingdomSettleService->canSettle($character)) {
            return response()->json([
                'message' => $this->kingdomSettleService->getErrorMessage()
            ], 422);
        }

        if (!$this->kingdomSettleService->canAfford()) {
            return response()->json([
                'message' => 'You don\'t have the gold.',
            ], 422);
        }

        if ($this->kingdomSettleService->canAfford($character)) {
            $amount = $character->kingdoms->count() * 10000;

            $character->update([
                'gold' => $character->gold - $amount,
            ]);

            event(new UpdateTopBarEvent($character->refresh()));
        }

        $this->kingdomSettleService->createKingdom($character, $request->name);

        return response()->json($this->kingdomSettleService->addKingdomToMap($character), 200);
    }
}
