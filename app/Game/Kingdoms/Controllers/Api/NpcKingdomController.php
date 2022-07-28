<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Game\Kingdoms\Events\UpdateGlobalMap;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;

class NpcKingdomController extends Controller {

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
    public function purchase(KingdomsSettleRequest $request, Character $character): JsonResponse {

        if (!$this->kingdomSettleService->canAfford($character)) {
            return response()->json([
                'message' => 'You don\'t have the gold to purchase this.',
            ], 422);
        }

        $kingdom = $this->kingdomSettleService->purchaseKingdom($character, $request->kingdom_id, $request->name);

        if (is_null($kingdom)) {
            return response()->json([
                'message' => 'Cannot purchase this.'
            ]);
        }

        $amount = $character->kingdoms->count() * 10000;

        $character->update([
            'gold' => $character->gold - $amount,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        return response()->json($this->kingdomSettleService->addKingdomToMap($character), 200);
    }
}
