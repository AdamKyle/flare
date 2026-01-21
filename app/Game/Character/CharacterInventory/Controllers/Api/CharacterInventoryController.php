<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Items\Enricher\ItemEnricherFactory;
use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Character\CharacterInventory\Requests\EquipItemValidation;
use App\Game\Character\CharacterInventory\Requests\InventoryActionRequest;
use App\Game\Character\CharacterInventory\Requests\MoveItemRequest;
use App\Game\Character\CharacterInventory\Requests\RemoveItemRequest;
use App\Game\Character\CharacterInventory\Requests\RenameSetRequest;
use App\Game\Character\CharacterInventory\Requests\SaveEquipmentAsSet;
use App\Game\Character\CharacterInventory\Requests\UseManyItemsValidation;
use App\Game\Character\CharacterInventory\Requests\ViewInventoryItemRequest;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterInventoryController extends Controller
{
    public function __construct(
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly UseItemService $useItemService,
    ) {}

    public function inventory(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->fetchCharacterInventory($request->per_page, $request->page, $request->search_text)
        );
    }

    public function questItems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->fetchCharacterQuestItems($request->per_page, $request->page, $request->search_text)
        );
    }

    public function usableItems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->fetchCharacterUsableItems($request->per_page, $request->page, $request->search_text, $request->filters)
        );
    }

    public function equippedItems(Character $character): JsonResponse
    {

        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        return response()->json([
            'equipped' => $characterInventoryService->fetchEquipped(),
            'weapon_damage' => $character->getInformation()->buildDamage(ItemType::validWeapons()),
            'spell_damage' => $character->getInformation()->buildDamage(ItemType::SPELL_DAMAGE->value),
            'healing_amount' => $character->getInformation()->buildDamage(ItemType::SPELL_HEALING->value),
            'defence_amount' => $character->getInformation()->buildDefence(),
            'set_name' => $characterInventoryService->getSetName(),
        ]);
    }

    public function currentSets(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->getCharacterInventorySets($request->per_page, $request->page),
        );
    }

    public function getSetItems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->getSetItems($request->per_page, $request->page, $request->search_text, $request->filters),
        );
    }

    public function itemDetails(ViewInventoryItemRequest $request, Character $character, ItemEnricherFactory $itemEnricherFactory): JsonResponse
    {
        $slot = $this->characterInventoryService->getSlotForItemDetails($character, $request->slot_id);

        if (is_null($slot)) {
            return response()->json([
                'message' => "There's nothing here for that slot.",
            ]);
        }

        $payload = $itemEnricherFactory->buildItemData($slot->item, $slot);

        return response()->json($payload);
    }

    public function destroy(Request $request, Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->deleteItem($request->item_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function destroyAll(Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->destroyAllItemsInInventory();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function disenchantAll(Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItemsInInventory();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function moveToSet(MoveItemRequest $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->moveItemToSet($character, $request->slot_id, $request->move_to_set);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function renameSet(RenameSetRequest $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->renameInventorySet($character, $request->set_id, $request->set_name);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function saveEquippedAsSet(SaveEquipmentAsSet $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $request->move_to_set);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function removeFromSet(RemoveItemRequest $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->removeItemFromInventorySet($character, $request->inventory_set_id, $request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function emptySet(Character $character, InventorySet $inventorySet): JsonResponse
    {
        $result = $this->inventorySetService->emptySet($character, $inventorySet);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @throws Exception
     */
    public function equipItem(EquipItemValidation $request, Character $character, EquipItemService $equipItemService): JsonResponse
    {
        $result = $equipItemService->equipItem($character, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @throws Exception
     */
    public function unequipItem(Request $request, Character $character): JsonResponse
    {
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
     * @throws Exception
     */
    public function unequipAll(Request $request, Character $character): JsonResponse
    {
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

    public function equipItemSet(Character $character, InventorySet $inventorySet): JsonResponse
    {
        $result = $this->inventorySetService->equipSet($character, $inventorySet);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @throws Exception
     */
    public function useManyItems(UseManyItemsValidation $request, Character $character): JsonResponse
    {
        $result = $this->useItemService->useManyItemsFromInventory($character, $request->items_to_use);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * @throws Exception
     */
    public function useItem(Character $character, Item $item): JsonResponse
    {
        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function destroyAlchemyItem(Request $request, Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem($request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function destroyAllAlchemyItems(Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->destroyAllAlchemyItems();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function sellItem(InventoryActionRequest $request, Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->sellItem($request->item_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function disenchantItem(InventoryActionRequest $request, Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->disenchantItem($request->item_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function moveItemToSet() {}
}
