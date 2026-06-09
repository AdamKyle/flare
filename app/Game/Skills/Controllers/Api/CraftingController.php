<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
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
    use ChecksAutomationRestrictions, FactionLoyalty, ShouldShowCraftingEventButton;

    public function __construct(private CraftingService $craftingService) {}

    /**
     * @throws Exception
     */
    public function fetchItemsToCraft(Request $request, Character $character): JsonResponse
    {
        $perPage = $request->input('per_page');

        if ($perPage !== null) {
            $craftingParams = ['crafting_type' => $request->crafting_type];
            $searchText = (string) ($request->input('search_text') ?? '');
            $armourSubtype = (string) ($request->input('filters.armour_type') ?? '');
            $page = (int) $request->input('page', 1);

            $paginated = $this->craftingService->fetchPaginatedCraftableItems(
                $character,
                $craftingParams,
                (int) $perPage,
                $page,
                $searchText,
                $armourSubtype
            );

            return response()->json(array_merge($paginated, [
                'items' => $paginated['data'],
                'xp' => $this->craftingService->getCraftingXP($character, $request->crafting_type),
                'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->crafting_type),
                'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
                'inventory_count' => $this->craftingService->getInventoryCount($character),
            ]));
        }

        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character, $request->all()),
            'xp' => $this->craftingService->getCraftingXP($character, $request->crafting_type),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->crafting_type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    /**
     * @throws Exception
     */
    public function fetchItemsForClass(Request $request, Character $character): JsonResponse
    {
        if ($character->class->type()->isAlcoholic()) {
            return response()->json([
                'message' => 'Your class doesn\'t generally use weapons. Please select a different type.',
            ], 422);
        }

        if ($character->class->type()->isPrisoner()) {
            return response()->json([
                'message' => 'Your class can use any weapon and doesn\'t have a specific weapon type associated with this class. Please select a different type.',
            ], 422);
        }

        $craftingTypeForClass = $this->resolveCraftingTypeForClass($character);
        $craftingParams = ['crafting_type' => $craftingTypeForClass];

        $perPage = $request->input('per_page');

        if ($perPage !== null) {
            $paginated = $this->craftingService->fetchPaginatedCraftableItems(
                $character,
                $craftingParams,
                (int) $perPage,
                (int) $request->input('page', 1),
                (string) ($request->input('search_text') ?? '')
            );

            return response()->json(array_merge($paginated, [
                'items' => $paginated['data'],
                'xp' => $this->craftingService->getCraftingXP($character, $craftingTypeForClass),
                'show_craft_for_npc' => $this->showCraftForNpcButton($character, $craftingTypeForClass),
                'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
                'inventory_count' => $this->craftingService->getInventoryCount($character),
            ]));
        }

        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character, $craftingParams),
            'xp' => $this->craftingService->getCraftingXP($character, $craftingTypeForClass),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $craftingTypeForClass),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    /**
     * @throws Exception
     */
    public function craft(CraftingValidation $request, Character $character, CraftingService $craftingService): JsonResponse
    {
        $action = $request->craft_for_npc ? AutomationRestrictionService::START_FACTION_LOYALTY : AutomationRestrictionService::START_ITEM_CRAFTING;

        $restriction = $this->automationRestrictionJsonResponse($character, $action);

        if (! is_null($restriction)) {
            return $restriction;
        }

        if (! $character->can_craft) {
            return response()->json(['message' => 'You must wait to craft again.'], 422);
        }

        $crafted = $craftingService->craft($character, $request->all());

        $perPage = $request->input('per_page');

        if ($perPage !== null) {
            $craftingParams = ['crafting_type' => $request->type];
            $searchText = (string) ($request->input('search_text') ?? '');
            $armourSubtype = (string) ($request->input('filters.armour_type') ?? '');

            $paginated = $this->craftingService->fetchPaginatedCraftableItems(
                $character->refresh(),
                $craftingParams,
                (int) $perPage,
                1,
                $searchText,
                $armourSubtype,
                false
            );

            return response()->json(array_merge($paginated, [
                'items' => $paginated['data'],
                'xp' => $this->craftingService->getCraftingXP($character, $request->type),
                'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
                'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->type),
                'inventory_count' => $this->craftingService->getInventoryCount($character),
                'crafted_item' => $crafted,
            ]), 200);
        }

        return response()->json([
            'items' => $this->craftingService->fetchCraftableItems($character->refresh(), ['crafting_type' => $request->type], false),
            'xp' => $this->craftingService->getCraftingXP($character, $request->type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->type),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
            'crafted_item' => $crafted,
        ], 200);
    }

    private function resolveCraftingTypeForClass(Character $character): string|array
    {
        $craftingTypes = ItemTypeMapping::getForClass($character->class->name);
        $craftingTypes = is_array($craftingTypes) ? $craftingTypes : [$craftingTypes];
        $validWeapons = ItemType::validWeapons();
        $filteredWeapons = array_values(array_filter($craftingTypes, fn ($type) => in_array($type, $validWeapons)));

        return count($filteredWeapons) === 1 ? $filteredWeapons[0] : $filteredWeapons;
    }
}
