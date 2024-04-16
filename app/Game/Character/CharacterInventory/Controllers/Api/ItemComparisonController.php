<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Game\Character\CharacterInventory\Requests\ComparisonFromChatValidate;
use App\Game\Character\CharacterInventory\Requests\ComparisonValidation;
use App\Game\Character\CharacterInventory\Services\CharacterGemBagService;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\ComparisonService;
use App\Http\Controllers\Controller;

class ItemComparisonController extends Controller {

    private ComparisonService $comparisonService;

    private CharacterInventoryService $characterInventoryService;

    private $gemBagService;

    public function __construct(ComparisonService $comparisonService, CharacterInventoryService $characterInventoryService, CharacterGemBagService $gemBagService) {
        $this->comparisonService         = $comparisonService;
        $this->characterInventoryService = $characterInventoryService;
        $this->gemBagService             = $gemBagService;
    }

    public function compareItem(ComparisonValidation $request, Character $character) {
        $inventory   = Inventory::where('character_id', $character->id)->first();
        $itemToEquip = InventorySlot::where('inventory_id', $inventory->id)->where('id', $request->slot_id)->first();

        if (is_null($itemToEquip)) {
            return response()->json(['message' => 'Item not found in your inventory.'], 422);
        }

        $type = $request->item_to_equip_type;

        if ($type === 'spell-healing' || $type === 'spell-damage') {
            $type = 'spell';
        }

        $data = $this->comparisonService->buildComparisonData($character, $itemToEquip, $type);

        return response()->json($data);
    }

    public function compareItemFromChat(ComparisonFromChatValidate $request, Character $character) {
        $inventory   = Inventory::where('character_id', $character->id)->first();
        $itemToEquip = InventorySlot::where('inventory_id', $inventory->id)->where('id', $request->id)->first();

        if (is_null($itemToEquip)) {

            $gemSlot  = $character->gemBag->gemSlots->find($request->id);

            if (!is_null($gemSlot)) {
                return response()->json([
                    'comparison_data' => [
                        'itemToEquip' => [
                            'item' => $this->gemBagService->getGemData($character, $gemSlot),
                            'type' => 'gem',
                        ]
                    ],
                    'usable_sets'     => $this->characterInventoryService->setCharacter($character)->getInventoryForType('usable_sets')
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
            'usable_sets'     => $this->characterInventoryService->setCharacter($character)->getInventoryForType('usable_sets')
        ]);
    }
}
