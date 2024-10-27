<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Events\Concerns\ShouldShowCraftingEventButton;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Skills\Requests\CraftingValidation;
use App\Game\Skills\Services\CraftingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CraftingController extends Controller
{
    use FactionLoyalty, ShouldShowCraftingEventButton;

    public function __construct(private CraftingService $craftingService) {}

    /**
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     */
    public function fetchItemsToCraft(Request $request, Character $character): JsonResponse
    {
        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character, $request->all()),
            'xp' => $this->craftingService->getCraftingXP($character, $request->crafting_type),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->crafting_type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
        ]);
    }

    /**
     * @param CraftingValidation $request
     * @param Character $character
     * @param CraftingService $craftingService
     * @return JsonResponse
     */
    public function craft(CraftingValidation $request, Character $character, CraftingService $craftingService): JsonResponse
    {
        if (! $character->can_craft) {
            return response()->json(['message' => 'You must wait to craft again.'], 422);
        }

        $crafted = $craftingService->craft($character, $request->all());

        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character->refresh(), ['crafting_type' => $request->type], false),
            'xp' => $this->craftingService->getCraftingXP($character, $request->type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->type),
            'crafted_item' => $crafted,
        ], 200);
    }
}
