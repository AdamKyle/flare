<?php

namespace App\Game\Core\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use League\Fractal\Resource\Item;
use League\Fractal\Manager;
use App\Flare\Models\Character;
use App\Flare\Models\Item as FlareItem;
use App\Flare\Transformers\CharacterInventoryTransformer;

class CharacterInventoryController extends Controller {

    private $manager;

    private $characterInventoryTransformer;

    public function __construct(Manager $manager, CharacterInventoryTransformer $characterInventoryTransformer) {
        $this->middleware('auth:api');

        $this->manager                       = $manager;
        $this->characterInventoryTransformer = $characterInventoryTransformer;
    }

    public function inventory(Character $character) {
        $inventory = new Item($character->inventory, $this->characterInventoryTransformer);

        return response()->json([
            'inventory' => $this->manager->createData($inventory)->toArray(),
        ], 200);
    }

    public function equipItem(Request $request, Character $character) {

        $request->validate([
            'type'    => 'required',
            'item_id' => 'required'
        ]);

        $item = FlareItem::find($request->item_id);

        $characterItem = $character->inventory->slots->filter(function($slot) use ($item) {
            return $slot->item->id === $item->id;
        })->first();

        if (is_null($characterItem)) {
            return response()->json([
                'message' => 'Cannot equip ' . $item->name . '. You do not currently have this in yor inventory.',
            ], 422);
        }

        if ($characterItem->equipped) {
            $equippedItem = $character->equippedItems->where('type', '=', $request->type)->where('item_id', '=', $characterItem->id)->first();

            if (!is_null($equippedItem)) {
                return response()->json([
                    'message' => 'Cannot equip ' . $characterItem->item->name . ' to the same hand.',
                ], 422);
            }

            $character->equippedItems()->create([
                'item_id' => $item->id,
                'type'    => $request->type,
            ]);

            $character->equippedItems->where('item_id', '=', $characterItem->id)->where('type', '!=', $request->type)->first()->delete();

            return response()->json([
                'message' => 'Switched: ' . $characterItem->item->name . ' to: ' . str_replace('-', ' ', Str::title($request->type)) . '.',
            ], 200);
        }

        $character->equippedItems()->create([
            'item_id' => $item->id,
            'type'    => $request->type,
        ]);

        return response()->json([
            'message' => 'Equipped: ' . $characterItem->item->name . ' to: ' . str_replace('-', ' ', Str::title($request->type)),
        ], 200);
    }
}
