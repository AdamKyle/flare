<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Skills\Requests\AlchemyValidation;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AlchemyController extends Controller
{

    /**
     * @param AlchemyService $alchemyService
     */
    public function __construct(private AlchemyService $alchemyService, private CraftingService $craftingService) {}

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function alchemyItems(Character $character): JsonResponse
    {
        return response()->json([
            'items' => $this->alchemyService->fetchAlchemistItems($character),
            'skill_xp' => $this->alchemyService->fetchSkillXP($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    /**
     * @param AlchemyValidation $request
     * @param Character $character
     * @return JsonResponse
     */
    public function transmute(AlchemyValidation $request, Character $character): JsonResponse
    {
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
