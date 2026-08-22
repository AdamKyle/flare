<?php

namespace App\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Character\CharacterInventory\Mappings\ItemTypeMapping;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Events\Concerns\ShouldShowCraftingEventButton;
use App\Game\Factions\FactionLoyalty\Concerns\FactionLoyalty;
use App\Game\Skills\Requests\CraftingValidation;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Transformers\CraftableItemTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CraftingController extends Controller
{
    use ChecksAutomationRestrictions, FactionLoyalty, ShouldShowCraftingEventButton;

    public function __construct(
        private CraftingService $craftingService,
        private readonly CraftableItemTransformer $craftableItemTransformer,
        private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer,
    ) {}

    /**
     * Fetch the Craftable items for the character, paginated when requested.
     */
    public function fetchItemsToCraft(Request $request, Character $character): JsonResponse
    {
        $perPage = $request->input('per_page');

        if ($perPage !== null) {
            $craftingType = $request->string('crafting_type')->toString();
            $craftingParams = ['crafting_type' => $craftingType];
            $searchText = $request->string('search_text', '')->toString();
            $armourSubtype = $request->string('filters.armour_type', '')->toString();
            $itemType = $request->string('filters.item_type', '')->toString();
            $page = $request->integer('page', 1);

            $paginated = $this->craftingService->fetchPaginatedCraftableItems(
                $character,
                $craftingParams,
                $request->integer('per_page'),
                $page,
                $searchText,
                $armourSubtype,
                $itemType
            );

            return response()->json(array_merge($paginated, [
                'items' => $paginated['data'],
                'xp' => $this->craftingService->getCraftingXP($character, $craftingType),
                'show_craft_for_npc' => $this->showCraftForNpcButton($character, $craftingType),
                'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
                'inventory_count' => $this->craftingService->getInventoryCount($character),
            ]));
        }

        return response()->json([
            'items' => $this->transformCraftableItems($this->craftingService->fetchCraftableItems($character, $request->all())),
            'xp' => $this->craftingService->getCraftingXP($character, $request->crafting_type),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->crafting_type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    /**
     * Fetch the Craftable items valid for the character's class, paginated when requested.
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
                $request->integer('per_page'),
                $request->integer('page', 1),
                $request->string('search_text', '')->toString()
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
            'items' => $this->transformCraftableItems($this->craftingService->fetchCraftableItems($character, $craftingParams)),
            'xp' => $this->craftingService->getCraftingXP($character, $craftingTypeForClass),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $craftingTypeForClass),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
        ]);
    }

    /**
     * Craft the requested item for the character and return the refreshed Craftable item list.
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

        $craftedInventorySlotId = $craftingService->getLastCraftedInventorySlotId();
        $resultPreview = $this->buildResultPreview($craftedInventorySlotId);

        $perPage = $request->input('per_page');

        if ($perPage !== null) {
            $craftingParams = ['crafting_type' => $request->string('type')->toString()];
            $searchText = $request->string('search_text', '')->toString();
            $armourSubtype = $request->string('filters.armour_type', '')->toString();

            $paginated = $this->craftingService->fetchPaginatedCraftableItems(
                $character->refresh(),
                $craftingParams,
                $request->integer('per_page'),
                1,
                $searchText,
                $armourSubtype,
                '',
                false
            );

            return response()->json(array_merge($paginated, [
                'items' => $paginated['data'],
                'xp' => $this->craftingService->getCraftingXP($character, $request->type),
                'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
                'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->type),
                'inventory_count' => $this->craftingService->getInventoryCount($character),
                'crafted_item' => $crafted,
                'crafted_inventory_slot_id' => $craftedInventorySlotId,
                'result_preview' => $resultPreview,
            ]), 200);
        }

        return response()->json([
            'items' => $this->transformCraftableItems($this->craftingService->fetchCraftableItems($character->refresh(), ['crafting_type' => $request->type], false)),
            'xp' => $this->craftingService->getCraftingXP($character, $request->type),
            'show_craft_for_event' => $this->shouldShowCraftingEventButton($character),
            'show_craft_for_npc' => $this->showCraftForNpcButton($character, $request->type),
            'inventory_count' => $this->craftingService->getInventoryCount($character),
            'crafted_item' => $crafted,
            'crafted_inventory_slot_id' => $craftedInventorySlotId,
            'result_preview' => $resultPreview,
        ], 200);
    }

    /**
     * Resolve the valid Crafting type(s) for the character's class.
     */
    private function resolveCraftingTypeForClass(Character $character): string|array
    {
        $craftingTypes = ItemTypeMapping::getForClass($character->class->name);
        $craftingTypes = is_array($craftingTypes) ? $craftingTypes : [$craftingTypes];
        $validWeapons = ItemType::validWeapons();
        $filteredWeapons = array_values(array_filter($craftingTypes, fn ($type) => in_array($type, $validWeapons)));

        return count($filteredWeapons) === 1 ? $filteredWeapons[0] : $filteredWeapons;
    }

    /**
     * Transform the given Craftable items for the API response.
     */
    private function transformCraftableItems(iterable $items): array
    {
        return (new Collection($items))
            ->map(fn ($item) => $this->craftableItemTransformer->transform($item))
            ->values()
            ->all();
    }

    /**
     * Build the crafted item result preview for the given inventory slot.
     */
    private function buildResultPreview(?int $inventorySlotId): ?array
    {
        if (is_null($inventorySlotId)) {
            return null;
        }

        $slot = InventorySlot::with(['item.itemPrefix', 'item.itemSuffix', 'item.appliedHolyStacks'])->find($inventorySlotId);

        if (is_null($slot) || is_null($slot->item)) {
            return null;
        }

        return $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id);
    }
}
