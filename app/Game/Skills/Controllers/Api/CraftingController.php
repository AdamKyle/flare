<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Game\Events\Concerns\ShouldShowCraftingEventButton;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Skills\Requests\CraftingValidation;
use App\Game\Skills\Services\CraftingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CraftingController extends Controller
{
    use FactionLoyalty, ShouldShowCraftingEventButton;

    /**
     * @var CraftingService
     */
    private $craftingService;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(CraftingService $craftingService)
    {
        $this->craftingService = $craftingService;
    }

    public function fetchItemsToCraft(Request $request, Character $character)
    {
        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character, $request->all()),
            'xp' => $this->craftingService->getCraftingXP($character, $request->crafting_type),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->crafting_type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
        ]);
    }

    public function craft(CraftingValidation $request, Character $character, CraftingService $craftingService)
    {
        if (! $character->can_craft) {
            return response()->json(['message' => 'invalid input.'], 429);
        }

        $crafted = $craftingService->craft($character, $request->all());

        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character->refresh(), ['crafting_type' => $request->type], false),
            'xp' => $this->craftingService->getCraftingXP($character, $request->type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'crafted_item' => $crafted,
        ], 200);
    }
}
