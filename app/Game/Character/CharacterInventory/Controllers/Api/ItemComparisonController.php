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
    private const UNSUPPORTED_EQUIPMENT_MESSAGE = 'Unable to determine how this item can be equipped. Please report this as a bug.';

    public function __construct(
        private readonly ComparisonService $comparisonService,
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly CharacterGemBagService $gemBagService,
    ) {}

    /**
     * Compare an owned inventory item with equipped gear.
     *
     * @param ComparisonValidation $request The validated comparison request.
     * @param Character $character The character performing the comparison.
     * @return JsonResponse The comparison response.
     */
    public function compareItem(ComparisonValidation $request, Character $character): JsonResponse
    {
        $inventory = Inventory::where('character_id', $character->id)->first();
        $itemToEquip = InventorySlot::where('inventory_id', $inventory->id)->where('id', $request->slot_id)->first();

        if (is_null($itemToEquip)) {
            return response()->json(['message' => 'Item not found in your inventory.'], 422);
        }

        $data = $this->comparisonService->buildComparisonData($character, $itemToEquip);

        if (is_null($data)) {
            return response()->json(['message' => self::UNSUPPORTED_EQUIPMENT_MESSAGE], 422);
        }

        return response()->json($data);
    }

    /**
     * Compare an item referenced from chat.
     *
     * @param ComparisonFromChatValidate $request The validated chat comparison request.
     * @param Character $character The character performing the comparison.
     * @return JsonResponse The comparison response.
     */
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

        $data = $this->comparisonService->buildComparisonData($character, $itemToEquip);

        if (is_null($data)) {
            return response()->json(['message' => self::UNSUPPORTED_EQUIPMENT_MESSAGE], 422);
        }

        return response()->json([
            'comparison_data' => $data,
            'usable_sets' => $this->characterInventoryService->setCharacter($character)->getInventoryForType('usable_sets'),
        ]);
    }
}
