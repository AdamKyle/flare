<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Values\AttackTypeValue;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Requests\FactionLoyaltyAutomationRequest;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Automation\Services\FactionLoyaltyAutomationService;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyAutomationController
{
    use ChecksAutomationRestrictions, FactionLoyalty;

    public function __construct(
        private readonly FactionLoyaltyAutomationService $factionLoyaltyAutomationService,
        private readonly FactionLoyaltyService $factionLoyaltyService,
    ) {}

    public function begin(FactionLoyaltyAutomationRequest $request, Character $character): JsonResponse
    {
        if (! AttackTypeValue::attackTypeExists($request->attack_type)) {
            return response()->json([
                'message' => 'Invalid attack type was selected. Please select from the drop down.',
            ], 422);
        }

        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::START_FACTION_LOYALTY);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $factionLoyalty = $this->getFactionLoyalty($character);

        if (is_null($factionLoyalty)) {
            return response()->json([
                'message' => 'You must be pledged to a faction before automating faction loyalty.',
            ], 422);
        }

        $factionLoyaltyNpc = $this->getNpcCurrentlyHelping($factionLoyalty);

        if (is_null($factionLoyaltyNpc)) {
            return response()->json([
                'message' => 'You must be assisting an NPC before automating faction loyalty.',
            ], 422);
        }

        if ($factionLoyaltyNpc->npc->gameMap->id !== $character->map->game_map_id) {
            return response()->json([
                'message' => 'You must be on the same map as the NPC you are assisting.',
            ], 422);
        }

        if (! $this->hasIncompleteTasks($factionLoyaltyNpc)) {
            return response()->json([
                'message' => 'This NPC does not have any incomplete tasks for you to automate.',
            ], 422);
        }

        $this->factionLoyaltyAutomationService->beginAutomation($character, $factionLoyaltyNpc, $request->attack_type);

        return response()->json([
            'message' => 'You have now begun automation to help out: '.$factionLoyaltyNpc->npc->real_name.' This will automatically end in 8 hours. You can manually end it at any time. Crafting has been disabled while faction loyalty automation is running. Keep an eye on the Automation tab to see your progress.',
        ]);
    }

    public function stop(Character $character): JsonResponse
    {
        $result = $this->factionLoyaltyAutomationService->stopAutomation($character);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function markWarningNoticeRead(Character $character): JsonResponse
    {
        $this->factionLoyaltyService->markLatestWarningNoticeRead($character);

        return response()->json();
    }
}
