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
     * @return JsonResponse
     * @throws Exception
     */
    public function unequipItem(Request $request, Character $character): JsonResponse {

        if ($request->inventory_set_equipped) {
            $result = $this->inventorySetService->unequipSet($character);

            $status = $result['status'];
            unset($result['status']);

            return response()->json($result, $status);
        }

        $result = $this->characterInventoryService->setCharacter($character)->unequipItem($request->item_to_remove);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Request $request
     * @param Character $character
     * @return JsonResponse
     * @throws Exception
     */
    public function unequipAll(Request $request, Character $character): JsonResponse {

        if ($request->is_set_equipped) {
            $result = $this->inventorySetService->unequipSet($character);

            $status = $result['status'];
            unset($result['status']);

            return response()->json($result, $status);
        }

        $result = $this->characterInventoryService->setCharacter($character)->unequipAllItems();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Character $character
     * @param InventorySet $inventorySet
     * @return JsonResponse
     */
    public function equipItemSet(Character $character, InventorySet $inventorySet): JsonResponse {
        $result = $this->inventorySetService->equipSet($character, $inventorySet);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
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
        $result = $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem($request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @param Character $character
     * @return JsonResponse
     */
    public function destroyAllAlchemyItems(Character $character): JsonResponse {
        $result = $this->characterInventoryService->setCharacter($character)->destroyAllAlchemyItems();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
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
