<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\InventorySet;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Requests\MoveItemRequest;
use App\Game\Core\Requests\RemoveItemRequest;
use App\Game\Core\Requests\SaveEquipmentAsSet;
use App\Game\Core\Services\InventorySetService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\User;
use App\Game\Core\Services\EquipItemService;
use App\Game\Core\Exceptions\EquipItemException;
use App\Game\Core\Requests\ComparisonValidation;
use App\Game\Core\Requests\EquipItemValidation;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Values\ValidEquipPositionsValue;
use Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class CharacterInventoryController extends Controller {

    private $equipItemService;

    private $characterTransformer;

    private $manager;

    public function __construct(EquipItemService $equipItemService, CharacterAttackTransformer $characterTransformer, Manager $manager) {

        $this->equipItemService     = $equipItemService;
        $this->characterTransformer = $characterTransformer;
        $this->manager              = $manager;

        $this->middleware('auth');

        $this->middleware('is.character.dead');

        $this->middleware('is.character.adventuring');
    }

    public function compare(
        ComparisonValidation $request,
        ValidEquipPositionsValue $validPositions,
        CharacterInventoryService $characterInventoryService,
        Character $character
    ) {

        $itemToEquip = InventorySlot::find($request->slot_id);

        if (is_null($itemToEquip)) {
            return redirect()->back()->with('error', 'Item not found in your inventory.');
        }

        $type = $request->item_to_equip_type;

        if ($type === 'spell-healing' || $type === 'spell-damage') {
            $type = 'spell';
        }

        $service = $characterInventoryService->setCharacter($character)
                                             ->setInventorySlot($itemToEquip)
                                             ->setPositions($validPositions->getPositions($itemToEquip->item))
                                             ->setInventory($type);

        $viewData = [
            'details'     => [],
            'itemToEquip' => $itemToEquip->item,
            'type'        => $service->getType($itemToEquip->item, $request->has('item_to_equip_type') ? $type : null),
            'slotId'      => $itemToEquip->id,
            'characterId' => $character->id,
            'bowEquipped' => false,
            'setEquipped' => false,
            'setIndex'    => 0,
        ];

        if ($service->inventory()->isNotEmpty()) {
            $setEquipped = $character->inventorySets()->where('is_equipped', true)->first();


            $hasSet   = !is_null($setEquipped);
            $setIndex = !is_null($setEquipped) ? $character->inventorySets->search(function($set) {return $set->is_equipped; }) + 1 : 0;

            $viewData = [
                'details'      => $this->equipItemService->setRequest($request)->getItemStats($itemToEquip->item, $service->inventory(), $character),
                'itemToEquip'  => $itemToEquip->item,
                'type'         => $service->getType($itemToEquip->item, $request->has('item_to_equip_type') ? $type : null),
                'slotId'       => $itemToEquip->id,
                'slotPosition' => $itemToEquip->position,
                'characterId'  => $character->id,
                'bowEquipped'  => $this->equipItemService->isBowEquipped($itemToEquip->item, $service->inventory()),
                'setEquipped'  => $hasSet,
                'setIndex'     => $setIndex,
            ];
        }


        Cache::put($character->user->id . '-compareItemDetails', $viewData, now()->addMinutes(10));

        return redirect()->to(route('game.inventory.compare-items', ['user' => $character->user]));
    }

    public function compareItem(User $user) {
        if (!Cache::has($user->id . '-compareItemDetails')) {
            return redirect()->route('game.character.sheet')->with('error', 'Item comparison expired.');
        }

        return view('game.character.equipment', Cache::get($user->id . '-compareItemDetails'));
    }

    public function equipItem(EquipItemValidation $request, Character $character) {
        try {
            $item = $this->equipItemService->setRequest($request)
                                           ->setCharacter($character)
                                           ->equipItem();

            if (auth()->user()->hasRole('Admin')) {
                return redirect()->to(route('admin.character.modeling.sheet', ['character' => $character]))->with('success', $item->affix_name . ' Equipped.');
            }

            return redirect()->to(route('game.character.sheet'))->with('success', $item->affix_name . ' Equipped.');

        } catch(EquipItemException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function unequipItem(Request $request, Character $character, InventorySetService $inventorySetService) {
        if ($request->inventory_set_equipped) {
            $inventorySet = $character->inventorySets()->where('is_equipped', true)->first();
            $inventoryIndex = $character->inventorySets->search(function($set) { return $set->is_equipped; });

            $inventorySetService->unEquipInventorySet($inventorySet);

            return redirect()->back()->with('success', 'Unequipped Set ' . $inventoryIndex + 1 . '.');
        }

        $foundItem = $character->inventory->slots->find($request->item_to_remove);

        if (is_null($foundItem)) {
            return redirect()->back()->with('error', 'No item found to be equipped.');
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        event(new UpdateTopBarEvent($character));

        $characterData = new ResourceItem($character->refresh(), $this->characterTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));

        return redirect()->back()->with('success', 'Unequipped item.');
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

        return redirect()->back()->with('success', 'All items have been removed.');
    }

    public function destroy(Request $request, Character $character) {

        $slot      = $character->inventory->slots->filter(function($slot) use ($request) {
            return $slot->id === (int) $request->slot_id;
        })->first();

        if (is_null($slot)) {
            return redirect()->back()->with('error', 'You don\'t own that item.');
        }

        if ($slot->equipped) {
            return redirect()->back()->with('error', 'Cannot destroy equipped item.');
        }

        $name = $slot ->item->affix_name;

        $slot->delete();

        $character->refresh();

        return redirect()->back()->with('success', 'Destroyed ' . $name . '.');
    }

    public function moveToSet(MoveItemRequest $request, Character $character, InventorySetService $inventorySetService) {
        $slot         = $character->inventory->slots()->find($request->slot_id);
        $inventorySet = $character->inventorySets()->find($request->move_to_set);

        if (is_null($slot) || is_null($inventorySet)) {
            return redirect()->back()->with('error', 'Either the slot or the inventory set does not exist.');
        }

        $itemName = $slot->item->affix_name;

        $inventorySetService->assignItemToSet($inventorySet, $slot);

        $character = $character->refresh();

        $index     = $character->inventorySets->search(function($set) use ($request) {
            return $set->id === $request->move_to_set;
        });

        return redirect()->back()->with('success', $itemName . ' Has been moved to: Set ' . $index + 1);
    }

    public function saveEquippedAsSet(SaveEquipmentAsSet $request, Character $character, InventorySetService $inventorySetService) {
        $currentlyEquipped = $character->inventory->slots->filter(function($slot) {
            return $slot->equipped;
        });

        $inventorySet = $character->inventorySets()->find($request->move_to_set);

        foreach ($currentlyEquipped as $equipped) {
            $inventorySet->slots()->create(array_merge(['inventory_set_id' => $inventorySet->id], $equipped->getAttributes()));

            $equipped->delete();
        }

        $inventorySet->update([
            'is_equipped' => true,
        ]);

        $character = $character->refresh();

        $setIndex = $character->inventorySets->search(function($set) use ($inventorySet) {
            return $set->equipped;
        });

        event(new UpdateTopBarEvent($character));

        $characterData = new ResourceItem($character, $this->characterTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));

        return redirect()->back()->with('success', 'Set ' . $setIndex + 1 . ' is now equipped (equipment has been moved to the set)');
    }

    public function removeFromSet(RemoveItemRequest $request, Character $character, InventorySetService $inventorySetService) {
        $slot = $character->inventorySets()->find($request->inventory_set_id)->slots()->find($request->slot_id);

        if (is_null($slot)) {
            return redirect()->back()->with('error', 'Either the slot or the inventory set does not exist.');
        }

        if ($slot->inventorySet->is_equipped) {
            return redirect()->back()->with('error', 'You cannot move an equipped item into your inventory from this set. Unequip it first.');
        }

        $itemName = $slot->item->affix_name;

        $result = $inventorySetService->removeItemFromInventorySet($slot->inventorySet, $slot->item);

        if ($result['status'] !== 200) {
            return redirect()->back()->with('error', $result['message']);
        }

        $character = $character->refresh();

        $index     = $character->inventorySets->search(function($set) use ($request) {
            return $set->id === $request->inventory_set_id;
        });

        return redirect()->back()->with('success', $itemName . ' Has been removed from Set ' . $index + 1 . ' and placed back into your inventory.');
    }

    public function equipSet(Character $character, InventorySet $inventorySet, InventorySetService $inventorySetService) {
        if (!$inventorySet->can_be_equipped) {
            return redirect()->back()->with('error', 'Set cannot be equipped.');
        }

        $inventorySetService->equipInventorySet($character, $inventorySet);

        $character->refresh();

        $setIndex = $character->inventorySets->search(function($set) {
            return $set->is_equipped;
        });

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $characterData = new ResourceItem($character, $this->characterTransformer);
        event(new UpdateAttackStats($this->manager->createData($characterData)->toArray(), $character->user));

        return redirect()->back()->with('success', 'Set ' . $setIndex + 1 . ' is now equipped');
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

        return redirect()->back()->with('success', 'Removed ' . $itemsRemoved . ' of ' . $originalInventorySetCount . ' items from Set ' . $setIndex + 1);
    }
}
