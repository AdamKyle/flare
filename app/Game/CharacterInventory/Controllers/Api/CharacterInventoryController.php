<?php

namespace App\Game\CharacterInventory\Controllers\Api;

use App\Game\CharacterInventory\Services\EquipBestItemForSlotsTypesService;
use App\Game\NpcActions\LabyrinthOracle\Events\LabyrinthOracleUpdate;
use Exception;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Models\Character;
use App\Flare\Transformers\ItemTransformer;
use App\Flare\Handlers\UpdateCharacterAttackTypes;
use App\Game\CharacterInventory\Requests\UseManyItemsValidation;
use App\Game\CharacterInventory\Requests\EquipItemValidation;
use App\Game\CharacterInventory\Requests\RemoveItemRequest;
use App\Game\CharacterInventory\Requests\RenameSetRequest;
use App\Game\CharacterInventory\Requests\SaveEquipmentAsSet;
use App\Game\CharacterInventory\Services\EquipItemService;
use App\Game\CharacterInventory\Services\UseItemService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\CharacterInventory\Services\CharacterInventoryService;
use App\Game\CharacterInventory\Requests\MoveItemRequest;
use App\Game\CharacterInventory\Services\InventorySetService;
use App\Http\Controllers\Controller;

class  CharacterInventoryController extends Controller {

    /**
     * @var CharacterInventoryService $characterInventoryService
     */
    private CharacterInventoryService $characterInventoryService;

    /**
     * @var UpdateCharacterAttackTypes $updateCharacterAttackTypes
     */
    private UpdateCharacterAttackTypes $updateCharacterAttackTypes;

    /**
     * @var UseItemService $useItemService
     */
    private UseItemService $useItemService;

    /**
     * @param CharacterInventoryService $characterInventoryService
     * @param UpdateCharacterAttackTypes $updateCharacterAttackTypes
     * @param UseItemService $useItemService
     */
    public function __construct(
        CharacterInventoryService $characterInventoryService,
        UpdateCharacterAttackTypes $updateCharacterAttackTypes,
        UseItemService $useItemService,
    ) {

        $this->characterInventoryService  = $characterInventoryService;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
        $this->useItemService = $useItemService;
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

        $slot = $character->inventory->slots->filter(function ($slot) use ($request) {
            return $slot->id === (int) $request->slot_id;
        })->first();

        if (is_null($slot)) {
            return response()->json(['message' => 'You don\'t own that item.'], 422);
        }

        if ($slot->equipped) {
            return response()->json(['message' => 'Cannot destroy equipped item.'], 422);
        }

        $name = $slot->item->affix_name;

        $item = null;

        if ($slot->item->type === 'artifact' && $slot->item->itemSkillProgressions->isNotEmpty()) {
            $item = $slot->item;
        }

        $slot->delete();

        if (!is_null($item)) {
            $item->itemSkillProgressions()->delete();

            $item->delete();
        }

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

        $items     = $character->inventory->slots->where('item.type', 'artifact')->whereNotNull('item.itemSkillProgressions')->pluck('item.id')->toArray();

        $character->inventory->slots()->whereIn('id', $slotIds)->delete();

        if (!empty($items)) {
            $items = Item::whereIn('id', $items)->get();

            foreach ($items as $item) {
                $item->itemSkillProgressions()->delete();

                $item->delete();
            }
        }

        $character = $character->refresh();

        $inventory = $this->characterInventoryService->setCharacter($character);

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

        $slots   = $inventory->getInventoryCollection()->filter(function ($slot) {
            return (!is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id));
        })->values();

        if ($slots->isNotEmpty()) {

            $response = $this->characterInventoryService->disenchantAllItems($slots, $character);

            unset($response['status']);

            return response()->json($response);
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

        event(new LabyrinthOracleUpdate($character));

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        if (is_null($inventorySet->name)) {
            $index     = $character->inventorySets->search(function ($set) use ($request) {
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
        $inventorySets = $character->inventorySets;
        $inventorySet  = $inventorySets->firstWhere('id', $request->set_id);

        if (is_null($inventorySet)) {
            return response()->json([
                'message' => 'Set does not exist.'
            ], 422);
        }

        if ($inventorySets->where('name', $request->set_name)->isNotEmpty()) {
            return response()->json([
                'message' => 'You already have a set with this name. Pick something else.'
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
        $currentlyEquipped = $character->inventory->slots->filter(function ($slot) {
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

        $setIndex = $character->inventorySets->search(function ($set) {
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
        if ($character->isInventoryFull()) {
            return response()->json([
                'message' => 'Your inventory is full. Cannot remove item from set.'
            ], 422);
        }

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
            $index     = $character->inventorySets->search(function ($set) use ($request) {
                return $set->id === $request->inventory_set_id;
            });

            $setName = 'Set ' . $index + 1;
        }

        event(new UpdateTopBarEvent($character));

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        $sets = $characterInventoryService->getInventoryForType('sets');

        return response()->json([
            'message' => $itemName . ' Has been removed from ' . $setName . ' and placed back into your inventory.',
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

        if ($character->isInventoryFull()) {
            return response()->json([
                'message' => 'Your inventory is full. Cannot remove items from set.'
            ], 422);
        }

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

        $setIndex = $character->inventorySets->search(function ($set) use ($inventorySet) {
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

            $equipItemService->setRequest($request->all())
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
        } catch (Exception $e) {
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
            $inventoryIndex = $character->inventorySets->search(function ($set) {
                return $set->is_equipped;
            });

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
                    'inventory'         => $inventory->getInventoryForType('inventory'),
                    'equipped'          => $inventory->getInventoryForType('equipped'),
                    'sets'              => $inventory->getInventoryForType('sets')['sets'],
                    'set_is_equipped'   => false,
                    'set_name_equipped' => $inventory->getEquippedInventorySetName(),
                    'usable_sets'       => $inventory->getUsableSets()
                ]
            ]);
        }

        if ($character->isInventoryFull()) {
            return response()->json([
                'message' => 'Your inventory is full. Cannot unequip item. You have no room in your inventory.'
            ], 422);
        }

        $foundItem = $character->inventory->slots->find($request->item_to_remove);

        if (is_null($foundItem)) {
            return response()->json(['message' => 'No item found to be unequipped.'], 422);
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        $character = $character->refresh();

        $this->updateCharacterAttackDataCache($character);

        event(new UpdateTopBarEvent($character->refresh()));

        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'message' => 'Unequipped item: ' . $foundItem->item->name,
            'inventory' => [
                'inventory'         => $inventory->getInventoryForType('inventory'),
                'equipped'          => $inventory->getInventoryForType('equipped'),
                'sets'              => $inventory->getInventoryForType('sets')['sets'],
                'set_is_equipped'   => false,
                'set_name_equipped' => $inventory->getEquippedInventorySetName(),
                'usable_sets'       => $inventory->getUsableSets()
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

            if ($character->isInventoryFull()) {
                return response()->json([
                    'message' => 'Your inventory is full. Cannot unequip items You have no room in your inventory.'
                ], 422);
            }

            $character->inventory->slots->each(function ($slot) {
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
                'sets'              => $characterInventoryService->getInventoryForType('sets')['sets'],
                'usable_sets'       => $characterInventoryService->getUsableSets()
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

        $setIndex = $character->inventorySets->search(function ($set) {
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
     * @param UseManyItemsValidation $request
     * @param Character $character
     * @return JsonResponse
     * @throws Exception
     */
    public function useManyItems(UseManyItemsValidation $request, Character $character): JsonResponse {

        $result = $this->useItemService->useManyItemsFromInventory($character, $request->items_to_use);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Character $character
     * @param Item $item
     * @return JsonResponse
     * @throws Exception
     */
    public function useItem(Character $character, Item $item): JsonResponse {
        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     */
    public function destroyAlchemyItem(Request $request, Character $character): JsonResponse {
        $slot = $character->inventory->slots->filter(function ($slot) use ($request) {
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
        $slots = $character->inventory->slots->filter(function ($slot) {
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
