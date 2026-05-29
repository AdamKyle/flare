<?php

namespace App\Game\Battle\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Request\FactionLoyaltyFight;
use App\Game\Battle\Services\FactionLoyaltyFightService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyBattleController extends Controller
{
    use ChecksAutomationRestrictions;

    public function __construct(private readonly FactionLoyaltyFightService $factionLoyaltyFightService) {}

    public function handleBountyTask(FactionLoyaltyFight $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::START_FACTION_LOYALTY);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->factionLoyaltyFightService->fightMonster($character, $request->monster_id, $request->npc_id, $request->attack_type);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
