<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Transformers\ItemTransformer;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterInventory\Requests\EquipItemValidation;
use App\Game\Character\CharacterInventory\Requests\MoveItemRequest;
use App\Game\Character\CharacterInventory\Requests\RemoveItemRequest;
use App\Game\Character\CharacterInventory\Requests\RenameSetRequest;
use App\Game\Character\CharacterInventory\Requests\SaveEquipmentAsSet;
use App\Game\Character\CharacterInventory\Requests\UseManyItemsValidation;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\CharacterInventory\Services\EquipBestItemForSlotsTypesService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\NpcActions\LabyrinthOracle\Events\LabyrinthOracleUpdate;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as FractalItem;

class  CharacterInventoryController extends Controller {

    /**
     * @var CharacterInventoryService $characterInventoryService
     */
    private CharacterInventoryService $characterInventoryService;

    /**
     * @var InventorySetService $inventorySetService
     */
    private InventorySetService $inventorySetService;

    /**
     * @var UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes
     */
    private UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes;

    /**
     * @var UseItemService $useItemService
     */
    private UseItemService $useItemService;

    /**
     * @param CharacterInventoryService $characterInventoryService
     * @param InventorySetService $inventorySetService
     * @param UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes
     * @param UseItemService $useItemService
     */
    public function __construct(
        CharacterInventoryService         $characterInventoryService,
        InventorySetService               $inventorySetService,
        UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes,
        UseItemService                    $useItemService,
    ) {

        $this->characterInventoryService  = $characterInventoryService;
        $this->inventorySetService = $inventorySetService;
        $this->updateCharacterAttackTypes = $updateCharacterAttackTypes;
        $this->useItemService = $useItemService;
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function inventory(Character $character): JsonResponse {
        $inventory = $this->characterInventoryService->setCharacter($character);

        return response()->json($inventory->getInventoryForApi());
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

        $result = $this->characterInventoryService->setCharacter($character->refresh())->deleteItem($request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function destroyAll(Character $character): JsonResponse {
        $result = $this->characterInventoryService->setCharacter($character)->destroyAllItemsInInventory();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function disenchantAll(Character $character): JsonResponse {
        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItemsInInventory();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param MoveItemRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function moveToSet(MoveItemRequest $request, Character $character): JsonResponse {
        $result = $this->inventorySetService->moveItemToSet($character, $request->slot_id, $request->move_to_set);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param RenameSetRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function renameSet(RenameSetRequest $request, Character $character): JsonResponse {
        $result = $this->inventorySetService->renameInventorySet($character, $request->set_id, $request->set_name);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param SaveEquipmentAsSet $request
     * @param Character $character
     * @return JsonResponse
     */
    public function saveEquippedAsSet(SaveEquipmentAsSet $request, Character $character): JsonResponse {
        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $request->move_to_set);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param RemoveItemRequest $request
     * @param Character $character
     * @return JsonResponse
     */
    public function removeFromSet(RemoveItemRequest $request, Character $character): JsonResponse {

        $result = $this->inventorySetService->removeItemFromInventorySet($character, $request->inventory_set_id, $request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Character $character
     * @param InventorySet $inventorySet
     * @return JsonResponse
     */
    public function emptySet(Character $character, InventorySet $inventorySet): JsonResponse {

        $result = $this->inventorySetService->emptySet($character, $inventorySet);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
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
