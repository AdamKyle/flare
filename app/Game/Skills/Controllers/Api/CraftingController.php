<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Events\Concerns\ShouldShowCraftingEventButton;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Skills\Requests\CraftingValidation;
use App\Game\Skills\Services\CraftingService;
use App\Http\Controllers\Controller;
use Exception;
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
     * @throws Exception
     */
    public function fetchItemsToCraft(Request $request, Character $character): JsonResponse
    {
        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character, $request->all()),
            'xp' => $this->craftingService->getCraftingXP($character, $request->crafting_type),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->crafting_type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character)
        ]);
    }

    /**
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     * @throws Exception
     */
    public function fetchItemsForClass(Request $request, Character $character): JsonResponse {

        if ($character->class->type()->isAlcoholic()) {
            return response()->json([
                'message' => 'Your class doesn\'t generally use weapons. Please select a different type.'
            ], 422);
        }

        if ($character->class->type()->isPrisoner()) {
            return response()->json([
                'message' => 'Your class can use any weapon and doesn\'t have a specific weapon type associated with this class. Please select a different type.'
            ], 422);
        }

        $craftingTypes = ItemTypeMapping::getForClass($character->class->name);
        $craftingTypes = is_array($craftingTypes) ? $craftingTypes : [$craftingTypes];

        $validWeapons = ItemType::validWeapons();
        $filteredWeapons = array_values(array_filter($craftingTypes, fn($type) => in_array($type, $validWeapons)));

        $craftingTypeForClass = count($filteredWeapons) === 1 ? $filteredWeapons[0] : $filteredWeapons;

        $params = ['crafting_type' => $craftingTypeForClass];

        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character, $params),
            'xp' => $this->craftingService->getCraftingXP($character, $craftingTypeForClass),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $craftingTypeForClass),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character)
        ]);
    }

    /**
     * @param CraftingValidation $request
     * @param Character $character
     * @param CraftingService $craftingService
     * @return JsonResponse
     * @throws Exception
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
            'inventory_count' => $this->craftingService->getInventoryCount($character),
            'crafted_item' => $crafted,
        ], 200);
    }
}
