<?php

namespace App\Game\Automation\FactionLoyalty\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\FactionLoyalty\Requests\FactionLoyaltyAutomationRequest;
use App\Game\Automation\FactionLoyalty\Services\FactionLoyaltyAutomationService;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyAutomationController
{
    use ChecksAutomationRestrictions, FactionLoyalty;

    /**
     * @param  FactionLoyaltyAutomationService  $factionLoyaltyAutomationService  The Faction Loyalty automation service.
     */
    public function __construct(
        private readonly FactionLoyaltyAutomationService $factionLoyaltyAutomationService,
    ) {}

    /**
     * Start Faction Loyalty automation for the character with the validated request options.
     *
     * @param  FactionLoyaltyAutomationRequest  $request  The validated Faction Loyalty start request.
     * @param  Character  $character  The character starting Faction Loyalty automation.
     * @return JsonResponse The start confirmation or validation error response.
     */
    public function begin(FactionLoyaltyAutomationRequest $request, Character $character): JsonResponse
    {
        if (! AttackType::attackTypeExists($request->attack_type)) {
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

    /**
     * Stop the character's active Faction Loyalty automation.
     *
     * @param  Character  $character  The character stopping Faction Loyalty automation.
     * @return JsonResponse The stop confirmation response.
     */
    public function stop(Character $character): JsonResponse
    {
        $result = $this->factionLoyaltyAutomationService->stopAutomation($character);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
