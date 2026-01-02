<?php

namespace App\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Kingdoms\Requests\KingdomsSettleRequest;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class KingdomSettleController extends Controller
{
    private KingdomSettleService $kingdomSettleService;

    public function __construct(KingdomSettleService $kingdomSettleService)
    {
        $this->kingdomSettleService = $kingdomSettleService;
    }

    public function settle(KingdomsSettleRequest $request, Character $character): JsonResponse
    {

        $result = $this->kingdomSettleService->settlePreCheck($character, $request->name);

        if (! empty($result)) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        if (! $this->kingdomSettleService->canSettle($character)) {
            return response()->json([
                'message' => $this->kingdomSettleService->getErrorMessage(),
            ], 422);
        }

        if (! $this->kingdomSettleService->canAfford($character)) {
            return response()->json([
                'message' => 'You don\'t have the gold.',
            ], 422);
        }

        $amount = $character->kingdoms->count() * 10000;

        $character->update([
            'gold' => $character->gold - $amount,
        ]);

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));

        $this->kingdomSettleService->createKingdom($character, $request->name);

        return response()->json($this->kingdomSettleService->addKingdomToMap($character), 200);
    }
}
