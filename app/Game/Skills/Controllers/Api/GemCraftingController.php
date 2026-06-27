<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Skills\Requests\GemCraftingValidation;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\GemService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class GemCraftingController extends Controller
{
    use ChecksAutomationRestrictions;

    public function __construct(private GemService $gemService, private CraftingService $craftingService) {}

    public function getCraftableItems(Character $character): JsonResponse
    {
        return response()->json([
            'tiers' => $this->gemService->getCraftableTiers($character),
            'skill_xp' => $this->gemService->fetchSkillXP($character),
            'inventory_count' => $this->craftingService->getGemBagCount($character),
        ]);
    }

    public function craftGem(Character $character, GemCraftingValidation $request): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::START_CRAFTING);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->gemService->generateGem($character, $request->tier);

        $status = $result['status'];
        unset($result['status']);

        $result['tiers'] = $this->gemService->getCraftableTiers($character);
        $result['skill_xp'] = $this->gemService->fetchSkillXP($character);
        $result['inventory_count'] = $this->craftingService->getGemBagCount($character);

        return response()->json($result, $status);
    }
}
