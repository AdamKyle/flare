<?php

namespace App\Game\Automation\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use App\Game\Automation\Requests\FactionLoyaltyAutomationRequest;
use App\Game\Automation\Services\FactionLoyaltyAutomationService;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use Illuminate\Http\JsonResponse;

class FactionLoyaltyAutomationController
{
    use FactionLoyalty;

    /**
     * @param FactionLoyaltyAutomationService $factionLoyaltyAutomationService
     */
    public function __construct(private readonly FactionLoyaltyAutomationService $factionLoyaltyAutomationService) {}

    /**
     * @param FactionLoyaltyAutomationRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function begin(FactionLoyaltyAutomationRequest $request, Character $character) {
        if (! AttackTypeValue::attackTypeExists($request->attack_type)) {
            return response()->json([
                'message' => 'Invalid attack type was selected. Please select from the drop down.',
            ], 422);
        }

        if ($character->currentAutomations()
                ->where('character_id', $character->id)
                ->where('completed_at', '>', now())
                ->where(function ($query) {
                    $query->where('type', AutomationType::DELVE)
                        ->orWhere('type', AutomationType::EXPLORING)
                        ->orWhere('type', AutomationType::FACTION_LOYALTY);
                })
                ->count() > 0
        ) {
            return response()->json([
                'message' => 'Nope. You already have one automation in progress.',
            ], 422);
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

        if (!$this->hasIncompleteTasks($factionLoyaltyNpc)) {
            return response()->json([
                'message' => 'This NPC does not have any incomplete tasks for you to automate.'
            ], 422);
        }

        $this->factionLoyaltyAutomationService->beginAutomation($character);

        return response()->json([
            'message' => 'You have now begun automation to help out: ' . $factionLoyaltyNpc->npc->real_name . ' This will automatically end in 8 hours. You can manually end it at any time. Keep an eye on the Automation tab to see your progress.',
        ]);
    }

    public function stop(Character $character) {

    }
}