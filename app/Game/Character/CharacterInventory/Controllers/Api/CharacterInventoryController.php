<?php

namespace App\Game\Character\CharacterInventory\Controllers\Api;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\Item;
use App\Flare\Pagination\Requests\PaginationRequest;
use App\Game\Automation\Concerns\ChecksAutomationRestrictions;
use App\Game\Automation\Services\AutomationRestrictionService;
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
use App\Game\Character\CharacterInventory\Services\InventorySetService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Values\ItemType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterInventoryController extends Controller
{
    use ChecksAutomationRestrictions;

    /**
     * @param  CharacterInventoryService  $characterInventoryService
     * @param  InventorySetService  $inventorySetService
     * @param  UseItemService  $useItemService
     */
    public function __construct(
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly InventorySetService $inventorySetService,
        private readonly UseItemService $useItemService,
    ) {}

    /**
     * Return the character's paginated normal inventory.
     *
     * @param  PaginationRequest  $request  The validated pagination/search request.
     * @param  Character  $character  The character requesting their inventory.
     * @return JsonResponse The paginated inventory payload.
     */
    public function inventory(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->fetchCharacterInventory($request->per_page, $request->page, $request->search_text)
        );
    }

    /**
     * Return the character's paginated quest items.
     *
     * @param  PaginationRequest  $request  The validated pagination/search request.
     * @param  Character  $character  The character requesting their quest items.
     * @return JsonResponse The paginated quest item payload.
     */
    public function questItems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->fetchCharacterQuestItems($request->per_page, $request->page, $request->search_text)
        );
    }

    /**
     * Return the character's paginated usable items.
     *
     * @param  PaginationRequest  $request  The validated pagination/search/filter request.
     * @param  Character  $character  The character requesting their usable items.
     * @return JsonResponse The paginated usable item payload.
     */
    public function usableItems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->fetchCharacterUsableItems($request->per_page, $request->page, $request->search_text, $request->filters)
        );
    }

    /**
     * Return the character's currently equipped items and derived combat totals.
     *
     * @param  Character  $character  The character requesting their equipped items.
     * @return JsonResponse The equipped items and derived combat totals.
     */
    public function equippedItems(Character $character): JsonResponse
    {
        $characterInventoryService = $this->characterInventoryService->setCharacter($character);

        $equipped = $characterInventoryService->fetchEquipped();

        return response()->json([
            'equipped' => ['data' => $equipped['data'] ?? []],
            'weapon_damage' => $character->getInformation()->buildDamage(ItemType::validWeapons()),
            'spell_damage' => $character->getInformation()->buildDamage(ItemType::SPELL_DAMAGE->value),
            'healing_amount' => $character->getInformation()->buildHealing(),
            'defence_amount' => $character->getInformation()->buildDefence(),
            'set_name' => $characterInventoryService->getSetName(),
        ]);
    }

    /**
     * Return the character's paginated Inventory Sets.
     *
     * @param  PaginationRequest  $request  The validated pagination request.
     * @param  Character  $character  The character requesting their Inventory Sets.
     * @return JsonResponse The paginated Inventory Set payload.
     */
    public function currentSets(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->getCharacterInventorySets($request->per_page, $request->page),
        );
    }

    /**
     * Return the paginated items belonging to one of the character's Inventory Sets.
     *
     * @param  PaginationRequest  $request  The validated pagination/search/filter request.
     * @param  Character  $character  The character requesting the set items.
     * @return JsonResponse The paginated set item payload.
     */
    public function getSetItems(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->getSetItems($request->per_page, $request->page, $request->search_text, $request->filters),
        );
    }

    /**
     * Return the character's paginated selectable Inventory Set options.
     *
     * @param  PaginationRequest  $request  The validated pagination/search request.
     * @param  Character  $character  The character requesting the set options.
     * @return JsonResponse The paginated set option payload.
     */
    public function setOptions(PaginationRequest $request, Character $character): JsonResponse
    {
        return response()->json(
            $this->characterInventoryService->setCharacter($character)->getPaginatedInventorySetOptions($request->per_page, $request->page, $request->search_text),
        );
    }

    /**
     * Return the enriched item data for one inventory slot.
     *
     * @param  ViewInventoryItemRequest  $request  The validated item detail request.
     * @param  Character  $character  The character requesting the item details.
     * @param  ItemEnricherFactory  $itemEnricherFactory  The factory used to build the enriched item payload.
     * @return JsonResponse The enriched item payload, or an error when the slot cannot be resolved.
     */
    public function itemDetails(ViewInventoryItemRequest $request, Character $character, ItemEnricherFactory $itemEnricherFactory): JsonResponse
    {
        $slot = $this->characterInventoryService->getSlotForItemDetails($character, $request->slot_id);

        if (is_null($slot)) {
            return response()->json([
                'message' => "There's nothing here for that slot.",
            ], 422);
        }

        $payload = $itemEnricherFactory->buildItemData($slot->item, $slot);

        return response()->json($payload);
    }

    /**
     * Destroy one item from the character's inventory.
     *
     * @param  Request  $request  The incoming request containing the item id to destroy.
     * @param  Character  $character  The character destroying the item.
     * @return JsonResponse The service's destroy result, or the automation restriction response when blocked.
     */
    public function destroy(Request $request, Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->characterInventoryService->setCharacter($character)->deleteItem($request->item_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Destroy every item in the character's normal inventory.
     *
     * @param  Character  $character  The character destroying their inventory.
     * @return JsonResponse The service's destroy-all result, or the automation restriction response when blocked.
     */
    public function destroyAll(Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->characterInventoryService->setCharacter($character)->destroyAllItemsInInventory();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Disenchant every disenchantable item in the character's normal inventory.
     *
     * @param  Character  $character  The character disenchanting their inventory.
     * @return JsonResponse The service's disenchant-all result, or the automation restriction response when blocked.
     */
    public function disenchantAll(Character $character): JsonResponse
    {
        $restriction = $this->automationRestrictionJsonResponse($character, AutomationRestrictionService::INVENTORY_MANAGEMENT);

        if (! is_null($restriction)) {
            return $restriction;
        }

        $result = $this->characterInventoryService->setCharacter($character)->disenchantAllItemsInInventory();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Move one inventory item into an Inventory Set.
     *
     * @param  MoveItemRequest  $request  The validated move-item request.
     * @param  Character  $character  The character moving the item.
     * @return JsonResponse The set service's move result.
     */
    public function moveToSet(MoveItemRequest $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->moveItemToSet($character, $request->slot_id, $request->set_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Rename one of the character's Inventory Sets.
     *
     * @param  RenameSetRequest  $request  The validated rename-set request.
     * @param  Character  $character  The character renaming the set.
     * @return JsonResponse The set service's rename result.
     */
    public function renameSet(RenameSetRequest $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->renameInventorySet($character, $request->set_id, $request->set_name);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Save the character's currently equipped items into an Inventory Set.
     *
     * @param  SaveEquipmentAsSet  $request  The validated save-equipped-as-set request.
     * @param  Character  $character  The character saving their equipped items.
     * @return JsonResponse The set service's save result.
     */
    public function saveEquippedAsSet(SaveEquipmentAsSet $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->saveEquippedItemsToSet($character, $request->move_to_set);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Remove one item from an Inventory Set back into the normal inventory.
     *
     * @param  RemoveItemRequest  $request  The validated remove-from-set request.
     * @param  Character  $character  The character removing the item.
     * @return JsonResponse The set service's remove result.
     */
    public function removeFromSet(RemoveItemRequest $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->removeItemFromInventorySet($character, $request->inventory_set_id, $request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Empty every item out of an Inventory Set back into the normal inventory.
     *
     * @param  Character  $character  The character emptying the set.
     * @param  InventorySet  $inventorySet  The Inventory Set being emptied.
     * @return JsonResponse The set service's empty-set result.
     */
    public function emptySet(Character $character, InventorySet $inventorySet): JsonResponse
    {
        $result = $this->inventorySetService->emptySet($character, $inventorySet);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Equip one item for the character.
     *
     * @param  EquipItemValidation  $request  The validated equip-item request.
     * @param  Character  $character  The character equipping the item.
     * @param  EquipItemService  $equipItemService  The service performing the equip operation.
     * @return JsonResponse The equip service's result.
     */
    public function equipItem(EquipItemValidation $request, Character $character, EquipItemService $equipItemService): JsonResponse
    {
        $result = $equipItemService->equipItem($character, $request->all());

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Unequip one item, or the character's equipped Inventory Set, for the character.
     *
     * @param  Request  $request  The incoming request indicating whether a set or single item is being unequipped.
     * @param  Character  $character  The character unequipping the item or set.
     * @return JsonResponse The set service's or inventory service's unequip result.
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
     * Unequip every equipped item, or the character's equipped Inventory Set, for the character.
     *
     * @param  Request  $request  The incoming request indicating whether a set or all items are being unequipped.
     * @param  Character  $character  The character unequipping everything.
     * @return JsonResponse The set service's or inventory service's unequip-all result.
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

    /**
     * Equip every item in an Inventory Set for the character.
     *
     * @param  Character  $character  The character equipping the set.
     * @param  InventorySet  $inventorySet  The Inventory Set being equipped.
     * @return JsonResponse The set service's equip-set result.
     */
    public function equipItemSet(Character $character, InventorySet $inventorySet): JsonResponse
    {
        $result = $this->inventorySetService->equipSet($character, $inventorySet);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Use several inventory items at once for the character.
     *
     * @param  UseManyItemsValidation  $request  The validated use-many-items request.
     * @param  Character  $character  The character using the items.
     * @return JsonResponse The use-item service's result.
     */
    public function useManyItems(UseManyItemsValidation $request, Character $character): JsonResponse
    {
        $result = $this->useItemService->useManyItemsFromInventory($character, $request->items_to_use);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Use one inventory item for the character.
     *
     * @param  Character  $character  The character using the item.
     * @param  Item  $item  The item being used.
     * @return JsonResponse The use-item service's result.
     */
    public function useItem(Character $character, Item $item): JsonResponse
    {
        $result = $this->useItemService->useSingleItemFromInventory($character, $item);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Use one or all of an Alchemy Bag slot's items for the character.
     *
     * @param  Request  $request  The incoming request indicating whether every item in the slot is being used.
     * @param  Character  $character  The character using the Alchemy item.
     * @param  AlchemyBagSlot  $alchemyBagSlot  The Alchemy Bag slot being used.
     * @return JsonResponse The use-item service's result.
     */
    public function useAlchemyItem(Request $request, Character $character, AlchemyBagSlot $alchemyBagSlot): JsonResponse
    {
        $result = $request->boolean('use_all')
            ? $this->useItemService->useAllAlchemyItems($character, $alchemyBagSlot)
            : $this->useItemService->useSingleAlchemyItem($character, $alchemyBagSlot);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Destroy one Alchemy Bag slot for the character.
     *
     * @param  Request  $request  The incoming request containing the Alchemy Bag slot id to destroy.
     * @param  Character  $character  The character destroying the Alchemy item.
     * @return JsonResponse The inventory service's destroy result.
     */
    public function destroyAlchemyItem(Request $request, Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem($request->slot_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Destroy every item in the character's Alchemy Bag.
     *
     * @param  Character  $character  The character destroying their Alchemy Bag.
     * @return JsonResponse The inventory service's destroy-all-alchemy-items result.
     */
    public function destroyAllAlchemyItems(Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->destroyAllAlchemyItems();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Sell one inventory item for the character.
     *
     * @param  InventoryActionRequest  $request  The validated sell-item request.
     * @param  Character  $character  The character selling the item.
     * @return JsonResponse The inventory service's sell result.
     */
    public function sellItem(InventoryActionRequest $request, Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->sellItem($request->item_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Disenchant one inventory item for the character.
     *
     * @param  InventoryActionRequest  $request  The validated disenchant-item request.
     * @param  Character  $character  The character disenchanting the item.
     * @return JsonResponse The inventory service's disenchant result.
     */
    public function disenchantItem(InventoryActionRequest $request, Character $character): JsonResponse
    {
        $result = $this->characterInventoryService->setCharacter($character)->disenchantItem($request->item_id);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Return the equippability details for every item in an Inventory Set.
     *
     * @param  Character  $character  The character requesting the equippability details.
     * @param  InventorySet  $inventorySet  The Inventory Set being checked.
     * @return JsonResponse The set service's equippability details result.
     */
    public function inventorySetEquippabilityDetails(Character $character, InventorySet $inventorySet): JsonResponse
    {
        $result = $this->inventorySetService->fetchSetEquippablityDetails($character, $inventorySet);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    /**
     * Move one item between Inventory Sets.
     *
     * @param  MoveItemRequest  $request  The validated move-item request.
     * @param  Character  $character  The character moving the item.
     * @return JsonResponse The set service's move result.
     */
    public function moveItemToSet(MoveItemRequest $request, Character $character): JsonResponse
    {
        $result = $this->inventorySetService->moveItemToSet($character, $request->slot_id, $request->set_id, false, true);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
