<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Battle\Request\FactionLoyaltyFight;
use App\Game\Battle\Services\FactionLoyaltyFightService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyBattleController extends Controller
{
    public function __construct(private readonly FactionLoyaltyFightService $factionLoyaltyFightService) {}

    public function handleBountyTask(FactionLoyaltyFight $request, Character $character): JsonResponse
    {
        $result = $this->factionLoyaltyFightService->fightMonster($character, $request->monster_id, $request->npc_id, $request->attack_type);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
