<?php

namespace App\Game\Core\Controllers\Api;

use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Flare\Models\SetSlot;
use Exception;
use Illuminate\Http\JsonResponse;
use League\Fractal\Resource\Item as FractalItem;
use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Jobs\CharacterAttackTypesCacheBuilder;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Transformers\ItemTransformer;
use App\Game\Core\Events\CharacterInventoryDetailsUpdate;
use App\Game\Core\Exceptions\EquipItemException;
use App\Game\Core\Requests\EquipItemValidation;
use App\Game\Core\Requests\RemoveItemRequest;
use App\Game\Core\Requests\RenameSetRequest;
use App\Game\Core\Requests\SaveEquipmentAsSet;
use App\Game\Core\Services\EquipItemService;
use App\Game\Core\Services\UseItemService;
use App\Game\Skills\Services\EnchantingService;
use League\Fractal\Manager;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Skills\Jobs\DisenchantItem;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Requests\MoveItemRequest;
use App\Game\Core\Services\InventorySetService;

class  CharacterInventoryController extends Controller {

    /**
     * @var CharacterInventoryService $characterInventoryService
     */
    private CharacterInventoryService $characterInventoryService;

    /**
     * @var CharacterSheetBaseInfoTransformer $characterTransformer
     */
    private CharacterSheetBaseInfoTransformer $characterTransformer;

    /**
     * @var UpdateCharacterAttackTypes $updateCharacterAttackTypes
     */
    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    /**
     * @var EnchantingService $enchantingService
     */
    private EnchantingService $enchantingService;

    /**
     * @var Manager $manager
     */
    private Manager $manager;

    /**
     * @param CharacterInventoryService $characterInventoryService
     * @param CharacterSheetBaseInfoTransformer $characterTransformer
     * @param UpdateCharacterAttackTypes $updateCharacterAttackTypes
     * @param EnchantingService $enchantingService
     * @param Manager $manager
     */
    public function __construct(CharacterInventoryService $characterInventoryService,
                                CharacterSheetBaseInfoTransformer $characterTransformer,
                                UpdateCharacterAttackTypes $updateCharacterAttackTypes,
                                EnchantingService $enchantingService,
                                Manager $manager) {

        $this->characterInventoryService  = $characterInventoryService;
        $this->characterTransformer       = $characterTransformer;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
        $this->enchantingService          = $enchantingService;
        $this->manager                    = $manager;
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function inventory(Character $character): JsonResponse {
        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json($inventory->getInventoryForApi(), 200);
    }

    /**
     * @param Character $character
     * @param Item $item
     * @param Manager $manager
     * @param ItemTransformer $itemTransformer
     * @return JsonResponse
     */
    public function itemDetails(Character $character, Item $item, Manager $manager, ItemTransformer $itemTransformer): JsonResponse {

        $slot = $this->characterInventoryService->getSlotForItemDetails($character, $item);

        if (is_null($slot)) {
            return response()->json([
                'message' => 'You cannot do that.'
            ]);
        }

        $item = new FractalItem($slot->item, $itemTransformer);
        $item = $manager->createData($item)->toArray();

        return response()->json($item);
    }

    /**
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     */
    public function destroy(Request $request, Character $character): JsonResponse {

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

        $inventory = $this->characterInventoryService->setCharacter($character->refresh());

        return response()->json([
            'message' => 'Destroyed ' . $name . '.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
            ]
        ]);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function destroyAll(Character $character): JsonResponse {
        $inventory = $this->characterInventoryService->setCharacter($character);

        $slotIds   = $inventory->findCharacterInventorySlotIds();

        $character->inventory->slots()->whereIn('id', $slotIds)->delete();

        return response()->json([
            'message' => 'Destroyed All Items.',
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
            ]
        ], 200);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function disenchantAll(Character $character): JsonResponse {
        $inventory = $this->characterInventoryService->setCharacter($character);

        $slots   = $inventory->getInventoryCollection()->filter(function($slot) {
            return (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id));
        })->values();

        if ($slots->isNotEmpty()) {

            $this->characterInventoryService->disenchantAllItems($slots, $character);

            return response()->json(['message' => 'You can freely move about.
                Your inventory will update as items disenchant. Check chat to see
                the total gold dust earned.'
            ]);
        }

        return response()->json(['message' => 'You have nothing to disenchant.']);
    }

    /**
     * @param MoveItemRequest $request
     * @param Character $character
     * @param InventorySetService $inventorySetService
     * @return JsonResponse
     */
    public function moveToSet(MoveItemRequest $request, Character $character, InventorySetService $inventorySetService): JsonResponse {
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

        event(new UpdateTopBarEvent($character));

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        if (is_null($inventorySet->name)) {
            $index     = $character->inventorySets->search(function($set) use ($request) {
                return $set->id === $request->move_to_set;
            });

            return response()->json([
                'message'   => $itemName . ' Has been moved to: Set ' . $index + 1,
                'inventory' => [
                    'inventory' => $characterInventoryService->getInventoryForType('inventory'),
                    'sets'      => $characterInventoryService->getInventoryForType('sets')['sets']
                ]
            ]);
        }

        return response()->json([
            'message'   => $itemName . ' Has been moved to: ' . $inventorySet->name,
            'inventory' => [
                'inventory' => $characterInventoryService->getInventoryForType('inventory'),
                'sets'      => $characterInventoryService->getInventoryForType('sets')['sets']
            ]
        ]);
    }

    /**
     * @param RenameSetRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function renameSet(RenameSetRequest $request, Character $character): JsonResponse {
        $inventorySet = $character->inventorySets()->find($request->set_id);

        if (is_null($inventorySet)) {
            return response()->json([
                'message' => 'Set does not exist.'
            ], 422);
        }

        $inventorySet->update([
            'name' => $request->set_name
        ]);

        $inventory = $this->characterInventoryService->setCharacter($character->refresh());

        return response()->json([
            'message' => 'Renamed set to: ' . $request->set_name,
            'inventory' => [
                'sets'         => $inventory->getInventoryForType('sets')['sets'],
                'usable_sets'  => $inventory->getInventoryForType('usable_sets'),
                'savable_sets' => $inventory->getInventoryForType('savable_sets'),
            ]
        ]);
    }

    /**
     * @param SaveEquipmentAsSet $request
     * @param Character $character
     * @param InventorySetService $inventorySetService
     * @return JsonResponse
     */
    public function saveEquippedAsSet(SaveEquipmentAsSet $request, Character $character, InventorySetService $inventorySetService): JsonResponse {
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

        $setIndex = $character->inventorySets->search(function($set) {
            return $set->is_equipped;
        });

        $setName = 'Set ' . $setIndex + 1;

        if (!is_null($inventorySet->name)) {
            $setName = $inventorySet->name;
        }

        event(new UpdateTopBarEvent($character));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message'   => $setName . ' is now equipped (equipment has been moved to the set)',
            'inventory' => [
                'sets'            => $inventory->getInventoryForType('sets')['sets'],
                'usable_sets'     => $inventory->getInventoryForType('usable_sets'),
                'savable_sets'    => $inventory->getInventoryForType('savable_sets'),
                'set_is_equipped' => true,
            ]
        ]);
    }

    /**
     * @param RemoveItemRequest $request
     * @param Character $character
     * @param InventorySetService $inventorySetService
     * @return JsonResponse
     */
    public function removeFromSet(RemoveItemRequest $request, Character $character, InventorySetService $inventorySetService): JsonResponse {
        $slot = $character->inventorySets()->find($request->inventory_set_id)->slots()->find($request->slot_id);

        if (is_null($slot)) {
            return response()->json(['message' => 'Either the slot or the inventory set does not exist.'], 422);
        }

        if ($slot->inventorySet->is_equipped) {
            return response()->json(['message' => 'You cannot move an equipped item into your inventory from this set. Unequip it first.'], 422);
        }

        $itemName = $slot->item->affix_name;

        $result = $inventorySetService->removeItemFromInventorySet($slot->inventorySet, $slot->item);

        // Chances are no inventory space.
        if ($result['status'] !== 200) {
            return response()->json(['message' => $result['message']], 422);
        }

        $character = $character->refresh();

        $set  = InventorySet::find($request->inventory_set_id);

        if (!is_null($set->name)) {
            $setName = $set->name;
        } else {
            $index     = $character->inventorySets->search(function($set) use ($request) {
                return $set->id === $request->inventory_set_id;
            });

            $setName = 'Set ' . $index + 1;
        }

        event(new UpdateTopBarEvent($character));

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        $sets = $characterInventoryService->getInventoryForType('sets');

        return response()->json([
            'message' => $itemName . ' Has been removed from '.$setName.' and placed back into your inventory.',
            'inventory' => [
                'inventory' => $characterInventoryService->getInventoryForType('inventory'),
                'sets'      => $sets['sets'],
            ]
        ]);
    }

    /**
     * @param Character $character
     * @param InventorySet $inventorySet
     * @param InventorySetService $inventorySetService
     * @return JsonResponse
     */
    public function emptySet(Character $character, InventorySet $inventorySet, InventorySetService $inventorySetService): JsonResponse {
        $currentInventoryAmount    = $character->inventory_max - $inventorySet->slots->count();
        $originalInventorySetCount = $inventorySet->slots->count();
        $itemsRemoved              = 0;

        // Only grab the amount of items your inventory can hold.
        foreach ($inventorySet->slots()->take($currentInventoryAmount)->get() as $slot) {

            if ($currentInventoryAmount !== 0) {
                if ($inventorySetService->removeItemFromInventorySet($inventorySet, $slot->item)) {
                    $currentInventoryAmount -= 1;
                    $itemsRemoved           += 1;

                    continue;
                }

                // @codeCoverageIgnoreStart
                break;
                // @codeCoverageIgnoreEnd
            }
        }

        $setIndex = $character->inventorySets->search(function($set) use ($inventorySet) {
            return $set->id === $inventorySet->id;
        });

        if (is_null($inventorySet->name)) {
            $setName = 'Set ' . $setIndex + 1;
        } else {
            $setName = $inventorySet->name;
        }

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character->refresh()));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'Removed ' . $itemsRemoved . ' of ' . $originalInventorySetCount . ' items from ' . $setName,
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
                'sets' => $inventory->getInventoryForType('sets')['sets'],
            ]
        ]);
    }

    /**
     * @param EquipItemValidation $request
     * @param Character $character
     * @param EquipItemService $equipItemService
     * @return JsonResponse
     * @throws Exception
     */
    public function equipItem(EquipItemValidation $request, Character $character, EquipItemService $equipItemService): JsonResponse {
        try {

            $equipItemService->setRequest($request)
                             ->setCharacter($character)
                             ->replaceItem();

            $this->updateCharacterAttackDataCache($character);

            $characterInventoryService = $this->characterInventoryService->setCharacter($character);

            return response()->json([
                'inventory' => [
                    'inventory' => $characterInventoryService->fetchCharacterInventory(),
                    'equipped'  => $characterInventoryService->fetchEquipped(),
                    'sets'      => $characterInventoryService->getCharacterInventorySets(),
                ],
                'message'       => 'Equipped item.'
            ]);

        } catch(EquipItemException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * @param Request $request
     * @param Character $character
     * @param InventorySetService $inventorySetService
     * @return JsonResponse
     * @throws Exception
     */
    public function unequipItem(Request $request, Character $character, InventorySetService $inventorySetService): JsonResponse {
        if ($request->inventory_set_equipped) {
            $inventorySet = $character->inventorySets()->where('is_equipped', true)->first();
            $inventoryIndex = $character->inventorySets->search(function($set) { return $set->is_equipped; });

            $inventorySetService->unEquipInventorySet($inventorySet);

            $this->updateCharacterAttackDataCache($character);

            $inventoryName = 'Set ' . $inventoryIndex + 1;

            if (!is_null($inventorySet->name)) {
                $inventoryName = $inventorySet->name;
            }

            $character = $character->refresh();

            $inventory = $this->characterInventoryService->setCharacter($character);

            return response()->json([
                'message' => 'Unequipped ' . $inventoryName . '.',
                'inventory' => [
                    'set_is_equipped' => false,
                    'equipped'        => $inventory->getInventoryForType('equipped'),
                    'sets'            => $inventory->getInventoryForType('sets')['sets'],
                ]
            ]);
        }

        $foundItem = $character->inventory->slots->find($request->item_to_remove);

        if (is_null($foundItem)) {
            return response()->json(['error' => 'No item found to be unequipped.'], 422);
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        event(new UpdateTopBarEvent($character->refresh()));

        $this->updateCharacterAttackDataCache($character);

        $character = $character->refresh();

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'Unequipped item: ' . $foundItem->item->name,
            'inventory' => [
                'inventory' => $inventory->getInventoryForType('inventory'),
                'equipped'  => $inventory->getInventoryForType('equipped'),
            ]
        ]);
    }

    /**
     * @param Request $request
     * @param Character $character
     * @param InventorySetService $inventorySetService
     * @return JsonResponse
     * @throws Exception
     */
    public function unequipAll(Request $request, Character $character, InventorySetService $inventorySetService): JsonResponse {

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

        $this->updateCharacterAttackDataCache($character);

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'All items have been removed.',
            'inventory' => [
                'inventory'         => $characterInventoryService->getInventoryForType('inventory'),
                'equipped'          => $characterInventoryService->getInventoryForType('equipped'),
                'set_is_equipped'   => false,
                'set_name_equipped' => $characterInventoryService->getEquippedInventorySetName(),
                'sets'              => $characterInventoryService->getInventoryForType('sets')['sets']
            ]
        ], 200);
    }

    /**
     * @param Character $character
     * @param InventorySet $inventorySet
     * @param InventorySetService $inventorySetService
     * @return JsonResponse
     * @throws Exception
     */
    public function equipItemSet(Character $character, InventorySet $inventorySet, InventorySetService $inventorySetService): JsonResponse {
        if (!$inventorySet->can_be_equipped) {
            return response()->json(['message' => 'Set cannot be equipped.'], 422);
        }

        $inventorySetService->equipInventorySet($character, $inventorySet);

        $character->refresh();

        $setIndex = $character->inventorySets->search(function($set) {
            return $set->is_equipped;
        });

        $character = $character->refresh();

        $this->updateCharacterAttackDataCache($character);

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        $inventoryName = 'Set ' . $setIndex + 1;
        $set = $inventorySet->refresh();

        if (!is_null($set->name)) {
            $inventoryName = $set->name;
        }

        return response()->json([
            'message' => $inventoryName .  ' is now equipped',
            'inventory' => [
                'equipped'          => $characterInventoryService->getInventoryForType('equipped'),
                'sets'              => $characterInventoryService->getInventoryForType('sets')['sets'],
                'set_is_equipped'   => true,
                'set_name_equipped' => $characterInventoryService->getEquippedInventorySetName(),
            ]
        ]);
    }

    /**
     * @param Character $character
     * @param Item $item
     * @param UseItemService $useItemService
     * @return JsonResponse
     * @throws Exception
     */
    public function useItem(Character $character, Item $item, UseItemService $useItemService): JsonResponse {
        if ($character->boons->count() === 10) {
            return response()->json(['message' => 'You can only have a max of ten boons applied.
            Check active boons to see which ones you have. You can always cancel one by clicking on the row.'], 422);
        }

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id;
        })->first();

        if (is_null($slot)) {
            return response()->json(['message' => 'You don\'t have this item.'], 422);
        }

        $useItemService->useItem($slot, $character, $item);

        $this->updateCharacterAttackDataCache($character);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'Applied: ' . $item->name . ' for: ' . $item->lasts_for . ' Minutes.',
            'inventory' => [
                'usable_items' => $inventory->getInventoryForType('usable_items')
            ]
        ], 200);
    }

    /**
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     */
    public function destroyAlchemyItem(Request $request, Character $character): JsonResponse {
        $slot = $character->inventory->slots->filter(function($slot) use($request) {
            return $slot->id === $request->slot_id;
        })->first();

        if (is_null($slot)) {
            return response()->json([
                'message' => 'No item found.'
            ]);
        }

        $name = $slot->item->affix_name;

        $slot->delete();

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'Destroyed Alchemy Item: ' . $name . '.',
            'inventory' => [
                'usable_items' => $inventory->getInventoryForType('usable_items')
            ]
        ]);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function destroyAllAlchemyItems(Character $character): JsonResponse {
        $slots = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'alchemy';
        });

        foreach ($slots as $slot) {
            $slot->delete();
        }

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'Destroyed All Alchemy Items.',
            'inventory' => [
                'usable_items' => $inventory->getInventoryForType('usable_items')
            ]
        ]);
    }

    /**
     * Updates the character stats.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    protected function updateCharacterAttackDataCache(Character $character): void {
        $this->updateCharacterAttackTypes->updateCache($character);
    }
}
