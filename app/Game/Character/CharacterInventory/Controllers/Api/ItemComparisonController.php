<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Game\Character\CharacterInventory\Requests\ComparisonFromChatValidate;
use App\Game\Character\CharacterInventory\Requests\ComparisonValidation;
use App\Game\Character\CharacterInventory\Services\CharacterGemBagService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ItemComparisonController extends Controller
{
    public function __construct(
        private readonly ComparisonService $comparisonService,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly CharacterGemBagService $gemBagService,
    ) {}

    public function compareItem(ComparisonValidation $request, Character $character): JsonResponse
    {
        $inventory = Inventory::where('character_id', $character->id)->first();
        $itemToEquip = InventorySlot::where('inventory_id', $inventory->id)->where('id', $request->slot_id)->first();

        if (is_null($itemToEquip)) {
            return response()->json(['message' => 'Item not found in your inventory.'], 422);
        }

        $type = $request->item_to_equip_type ?? $itemToEquip->item->type;

        if ($type === 'spell-healing' || $type === 'spell-damage') {
            $type = 'spell';
        }

        $data = $this->comparisonService->buildComparisonData($character, $itemToEquip, $type);

        return response()->json($data);
    }

    public function compareItemFromChat(ComparisonFromChatValidate $request, Character $character): JsonResponse
    {
        if ($request->source === 'alchemy_bag') {
            $alchemyBagSlot = AlchemyBagSlot::where('id', $request->id)
                ->where('character_id', $character->id)
                ->where('alchemy_bag_id', $character->alchemyBag?->id)
                ->with('item')
                ->first();

            if (is_null($alchemyBagSlot)) {
                return response()->json(['message' => 'Item does not exist  ...'], 404);
            }

            return response()->json([
                'comparison_data' => $this->comparisonService->buildAlchemyBagComparisonData($character, $alchemyBagSlot),
                'usable_sets' => [],
            ]);
        }

        if ($request->source === 'crafted_items_set') {
            $setSlot = SetSlot::where('id', $request->id)
                ->whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id)
                    ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE))
                ->with('item')
                ->first();

            if (is_null($setSlot) || is_null($setSlot->item)) {
                return response()->json(['message' => 'Item does not exist  ...'], 404);
            }

            return response()->json([
                'comparison_data' => $this->comparisonService->buildSetSlotComparisonData($character, $setSlot),
                'usable_sets' => [],
            ]);
        }

        $inventory = Inventory::where('character_id', $character->id)->first();
        $itemToEquip = InventorySlot::where('inventory_id', $inventory->id)->where('id', $request->id)->first();

        if (is_null($itemToEquip)) {

            $gemSlot = $character->gemBag->gemSlots->find($request->id);

            if (! is_null($gemSlot)) {
                return response()->json([
                    'comparison_data' => [
                        'itemToEquip' => [
                            'item' => $this->gemBagService->getGemData($character, $gemSlot),
                            'type' => 'gem',
                        ],
                    ],
                    'usable_sets' => $this->characterInventoryService->setCharacter($character)->getInventoryForType('usable_sets'),
                ]);
            }
        }

        if (is_null($itemToEquip) && is_null($gemSlot)) {
            return response()->json(['message' => 'Item does not exist  ...'], 404);
        }

        if ($itemToEquip->equipped) {
            return response()->json(['message' => 'Item is no longer in your inventory.'], 404);
        }

        $type = $itemToEquip->item->type;

        if ($type === 'spell-healing' || $type === 'spell-damage') {
            $type = 'spell';
        }

        $data = $this->comparisonService->buildComparisonData($character, $itemToEquip, $type);

        return response()->json([
            'comparison_data' => $data,
            'usable_sets' => $this->characterInventoryService->setCharacter($character)->getInventoryForType('usable_sets'),
        ]);
    }
}
