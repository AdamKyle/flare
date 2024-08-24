<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Kingdoms\Requests\NPCKingdomPurchaseRequest;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class NpcKingdomController extends Controller
{
    private KingdomSettleService $kingdomSettleService;

    public function __construct(KingdomSettleService $kingdomSettleService)
    {
        $this->kingdomSettleService = $kingdomSettleService;
    }

    public function purchase(NPCKingdomPurchaseRequest $request, Character $character): JsonResponse
    {

        if (! $this->kingdomSettleService->canAfford($character)) {
            return response()->json([
                'message' => 'You don\'t have the gold to purchase this.',
            ], 422);
        }

        if (! is_null($character->can_settle_again_at)) {
            $timeDifference = now()->diffInMinutes($character->can_settle_again_at);

            return response()->json([
                'message' => 'You are not allowed to settle or purchase another kingdom at this time.
                You have selfishly abandoned your people in other kingdoms.
                You can settle again in: '.$timeDifference.' minutes',
            ], 422);
        }

        $kingdom = $this->kingdomSettleService->purchaseKingdom($character, $request->kingdom_id);

        if (is_null($kingdom)) {
            return response()->json([
                'message' => 'Cannot purchase this.',
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
