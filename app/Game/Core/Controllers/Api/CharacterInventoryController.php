<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Models\InventorySet;
use App\Game\Core\Requests\RemoveItemRequest;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Models\Character;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Skills\Jobs\DisenchantItem;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Requests\MoveItemRequest;
use App\Game\Core\Services\InventorySetService;

class CharacterInventoryController extends Controller {

    private $characterInventoryService;

    private $characterTransformer;

    private $manager;

    public function __construct(CharacterInventoryService $characterInventoryService,
                                CharacterAttackTransformer $characterTransformer, Manager $manager) {

        $this->characterInventoryService = $characterInventoryService;
        $this->characterTransformer      = $characterTransformer;
        $this->manager                   = $manager;
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

    public function removeFromSet(RemoveItemRequest $request, Character $character, InventorySetService $inventorySetService) {
        $slot = $character->inventorySets()->find($request->inventory_set_id)->slots()->find($request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'Either the slot or the inventory set does not exist.'], 422);
        }

        if ($slot->inventorySet->is_equipped) {
            return response()->json(['message' => 'You cannot move an equipped item into your inventory from this set. Unequip it first.'], 422);
        }

        $itemName = $slot->item->affix_name;

        $result = $inventorySetService->removeItemFromInventorySet($slot->inventorySet, $slot->item);

        if ($result['status'] !== 200) {
            return response()->json(['message' => $result['message']], 422);
        }

        $character = $character->refresh();

        $index     = $character->inventorySets->search(function($set) use ($request) {
            return $set->id === $request->inventory_set_id;
        });

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return response()->json(['message' => $itemName . ' Has been removed from Set ' . $index + 1 . ' and placed back into your inventory.'], 200);
    }

    public function emptySet(Character $character, InventorySet $inventorySet, InventorySetService $inventorySetService) {
        $currentInventoryAmount    = $character->inventory_max - $inventorySet->slots->count();
        $originalInventorySetCount = $inventorySet->slots->count();
        $itemsRemoved              = 0;

        foreach ($inventorySet->slots as $slot) {

            if ($currentInventoryAmount !== 0) {
                $inventorySetService->removeItemFromInventorySet($inventorySet, $slot->item);

                $currentInventoryAmount -= 1;
                $itemsRemoved           += 1;
            }
        }

        $setIndex = $character->inventorySets->search(function($set) use ($inventorySet) {
            return $set->id === $inventorySet->id;
        });

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return response()->json(['message' => 'Removed ' . $itemsRemoved . ' of ' . $originalInventorySetCount . ' items from Set ' . $setIndex + 1], 200);
    }

    public function unequipItem(Request $request, Character $character, InventorySetService $inventorySetService) {
        if ($request->inventory_set_equipped) {
            $inventorySet = $character->inventorySets()->where('is_equipped', true)->first();
            $inventoryIndex = $character->inventorySets->search(function($set) { return $set->is_equipped; });

            $inventorySetService->unEquipInventorySet($inventorySet);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            return response()->json(['message' => 'Unequipped Set ' . $inventoryIndex + 1 . '.'], 200);
        }

        $foundItem = $character->inventory->slots->find($request->item_to_remove);

        if (is_null($foundItem)) {
            return response()->json(['error' => 'No item found to be equipped.'], 422);
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        event(new UpdateTopBarEvent($character));

        $characterData = new ResourceItem($character->refresh(), $this->characterTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return response()->json(['message' => 'Unequipped item.'], 200);
    }

    public function unequipAll(Request $request, Character $character, InventorySetService $inventorySetService) {
        if ($request->is_set_equipped) {
            $inventorySet = $character->inventorySets()->where('is_equipped', true)->first();

            $inventorySetService->unEquipInventorySet($inventorySet);
        } else {
            $character->inventory->slots->each(function($slot) {
                $slot->update([
                    'equipped' => false,
                    'position' => null,
                ]);
            });
        }

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $characterData = new ResourceItem($character, $this->characterTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));

        event(new CharacterInventoryUpdateBroadCastEvent($character->user));

        return response()->json(['message' => 'All items have been removed.'], 200);
    }
}
