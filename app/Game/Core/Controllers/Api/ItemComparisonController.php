<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Game\Core\Requests\ComparisonValidation;
use App\Game\Core\Services\ComparisonService;
use App\Http\Controllers\Controller;

class ItemComparisonController extends Controller {

    public function __construct() {
    }

    public function compareItem(ComparisonValidation $request, Character $character, ComparisonService $comparisonService, ) {
        $inventory   = Inventory::where('character_id', $character->id)->first();
        $itemToEquip = InventorySlot::where('inventory_id', $inventory->id)->where('id', $request->slot_id)->first();

        if (is_null($itemToEquip)) {
            return response()->json(['message' => 'Item not found in your inventory.'], 422);
        }

        $type = $request->item_to_equip_type;

        if ($type === 'spell-healing' || $type === 'spell-damage') {
            $type = 'spell';
        }

        $data = $comparisonService->buildComparisonData($character, $itemToEquip, $type);

        return response()->json($data);
    }
}
