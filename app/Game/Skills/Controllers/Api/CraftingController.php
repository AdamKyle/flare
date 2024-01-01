<?php

namespace App\Game\Skills\Controllers\Api;

use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Skills\Requests\CraftingValidation;
use App\Game\Skills\Services\CraftingService;

class CraftingController extends Controller {

    use FactionLoyalty;

    /**
     * @var CraftingService $craftingService
     */
    private $craftingService;

    /**
     * Constructor
     *
     * @param CraftingService $craftingService
     * @return void
     */
    public function __construct(CraftingService $craftingService) {
        $this->craftingService = $craftingService;
    }

    public function fetchItemsToCraft(Request $request, Character $character) {
        return response()->json([
            'items'              => $this->craftingService->fetchCraftableItems($character, $request->all()),
            'xp'                 => $this->craftingService->getCraftingXP($character, $request->crafting_type),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->crafting_type),
        ]);
    }

    public function craft(CraftingValidation $request, Character $character, CraftingService $craftingService) {
        if (!$character->can_craft) {
            return response()->json(['message' => 'invalid input.'], 429);
        }

        $craftingService->craft($character, $request->all());

        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character->refresh(), ['crafting_type' => $request->type], false),
            'xp'    => $this->craftingService->getCraftingXP($character, $request->type),
        ], 200);
    }
}
