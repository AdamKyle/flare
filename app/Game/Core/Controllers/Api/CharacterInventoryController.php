<?php

namespace App\Game\Core\Controllers\Api;

use App\Game\Core\Requests\MoveItemRequest;
use App\Game\Core\Services\InventorySetService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Models\Character;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Skills\Jobs\DisenchantItem;
use App\Game\Core\Services\CharacterInventoryService;

class CharacterInventoryController extends Controller {

    private $characterInventoryService;

    public function __construct(CharacterInventoryService $characterInventoryService) {

        $this->characterInventoryService = $characterInventoryService;
    }

    public function inventory(Character $character) {
        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json($inventory->getInventoryForApi(), 200);
    }

    public function destroy(Request $request, Character $character) {

        $slot = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === (int) $request->slot_id;
        })->first();

        if (is_null($slot)) {
            return response()->json(['message' => 'You don\'t own that item.'], 422);
        }

        if ($slot->equipped) {
            return response()->json(['message' => 'Cannot destroy equipped item.'], 422);
        }

        $name = $slot ->item->affix_name;

        $slot->delete();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return response()->json(['message' => 'Destroyed ' . $name . '.'], 200);
    }

    public function destroyAll(Character $character) {
        $inventory = $this->characterInventoryService->setCharacter($character);

        $slotIds   = $inventory->fetchCharacterInventory()->pluck('id');

        $character->inventory->slots()->whereIn('id', $slotIds)->delete();

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return response()->json(['message' => 'Destroyed All Items.'], 200);
    }

    public function disenchantAll(Character $character) {
        $inventory = $this->characterInventoryService->setCharacter($character);

        $slots   = $inventory->fetchCharacterInventory()->filter(function($slot) {
            return (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id));
        })->values();

        if ($slots->isNotEmpty()) {
            $jobs = [];

            foreach ($slots as $index => $slot) {
                if ($index !== 0) {
                    dump($index . ' ' . ($slots->count() - 1));
                    if ($index === ($slots->count() - 1)) {
                        $jobs[] = new DisenchantItem($character, $slot->id, true);
                    } else {
                        $jobs[] = new DisenchantItem($character, $slot->id);
                    }
                }
            }

            DisenchantItem::withChain($jobs)->dispatch($character, $slots->first()->id);

            return response()->json(['message' => 'You can freely move about. 
                Your inventory will update as items disenchant. Check chat to see 
                the total gold dust earned.'
            ]);
        }

        return response()->json(['message' => 'You have nothing to disenchant.']);


    }

    public function moveToSet(MoveItemRequest $request, Character $character, InventorySetService $inventorySetService) {
        $slot         = $character->inventory->slots()->find($request->slot_id);
        $inventorySet = $character->inventorySets()->find($request->move_to_set);

        if (is_null($slot) || is_null($inventorySet)) {
            return response()->json([
                'message' => 'Either the slot or the inventory set does not exist.'
            ], 422);
        }

        $itemName = $slot->item->affix_name;

        $inventorySetService->assignItemToSet($inventorySet, $slot);

        $character = $character->refresh();

        $index     = $character->inventorySets->search(function($set) use ($request) {
            return $set->id === $request->move_to_set;
        });

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return response()->json([
            'message' => $itemName . ' Has been moved to: Set ' . $index + 1,
        ]);
    }
}
