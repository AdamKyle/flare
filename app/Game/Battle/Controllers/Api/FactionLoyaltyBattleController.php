<?php

namespace App\Game\Battle\Controllers\Api;

use App\Game\Battle\Services\FactionLoyaltyFightService;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Battle\Request\FactionLoyaltyFight;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyBattleController extends Controller {

    /**
     * @param FactionLoyaltyFightService $factionLoyaltyFightService
     */
    public function __construct(private readonly FactionLoyaltyFightService $factionLoyaltyFightService) {
    }

    /**
     * @param FactionLoyaltyFight $request
     * @param Character $character
     * @return JsonResponse
     */
    public function handleBountyTask(FactionLoyaltyFight $request, Character $character): JsonResponse {
        $result = $this->factionLoyaltyFightService->fightMonster($character, $request->monster_id, $request->npc_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
