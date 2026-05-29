<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Skills\Requests\AlchemyValidation;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AlchemyController extends Controller
{
    use ChecksAutomationRestrictions;

    /**
     * @param AlchemyService $alchemyService
     */
    public function __construct(private AlchemyService $alchemyService, private CraftingService $craftingService) {}

    public function alchemyItems(Character $character): JsonResponse
    {
        return response()->json([
            'items' => $this->alchemyService->fetchAlchemistItems($character),
            'skill_xp' => $this->alchemyService->fetchSkillXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    public function transmute(AlchemyValidation $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::START_CRAFTING);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if (! $character->can_craft) {
            return response()->json(['message' => 'You must wait to craft again.'], 422);
        }

        $this->alchemyService->transmute($character, $request->item_to_craft);

        return response()->json([
            'items' => $this->alchemyService->fetchAlchemistItems($character, false),
            'skill_xp' => $this->alchemyService->fetchSkillXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }
}
