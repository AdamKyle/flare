<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use Illuminate\Database\Eloquent\Collection;

class CharacterInventoryService
{
    private Character $character;

    /**
     * @param CharacterInventoryReadService $characterInventoryReadService
     * @param CharacterInventorySetReadService $characterInventorySetReadService
     * @param CharacterUsableInventoryService $characterUsableInventoryService
     * @param CharacterInventorySlotResolver $characterInventorySlotResolver
     * @param CharacterInventoryActionService $characterInventoryActionService
     * @param CharacterInventoryEquipmentService $characterInventoryEquipmentService
     */
    public function __construct(
        private readonly CharacterInventoryReadService $characterInventoryReadService,
        private readonly CharacterInventorySetReadService $characterInventorySetReadService,
        private readonly CharacterUsableInventoryService $characterUsableInventoryService,
        private readonly CharacterInventorySlotResolver $characterInventorySlotResolver,
        private readonly CharacterInventoryActionService $characterInventoryActionService,
        private readonly CharacterInventoryEquipmentService $characterInventoryEquipmentService,
    ) {}

    /**
     * Set the Character used by compatibility facade calls.
     *
     * @param Character $character
     * @return CharacterInventoryService
     */
    public function setCharacter(Character $character): CharacterInventoryService
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Return the complete Character Inventory API payload.
     *
     * @return array
     */
    public function getInventoryForApi(): array
    {
        $equipped = $this->characterInventoryReadService->equipped($this->character) ?? [];
        $usableSets = $this->characterInventorySetReadService->usableSets($this->character);
        $setName = $this->characterInventorySetReadService->equippedSetName($this->character);

        return [
            'inventory' => $this->fetchCharacterInventory(),
            'usable_sets' => $usableSets,
            'savable_sets' => $usableSets,
            'equipped' => $equipped,
            'sets' => $this->getCharacterInventorySets(),
            'quest_items' => $this->characterInventoryReadService->questItems($this->character),
            'usable_items' => $this->characterUsableInventoryService->usableItems($this->character),
            'set_is_equipped' => ! is_null($setName),
            'set_name_equipped' => $setName,
        ];
    }

    /**
     * Return the equipped Inventory Set name.
     *
     * @return string|null
     */
    public function getSetName(): ?string
    {
        return $this->getEquippedInventorySetName();
    }

    /**
     * Return the equipped Inventory Set name through the compatibility API.
     *
     * @return string|null
     */
    public function getEquippedInventorySetName(): ?string
    {
        return $this->characterInventorySetReadService->equippedSetName($this->character);
    }

    /**
     * Return Inventory data for a requested panel type.
     *
     * @param string $type
     * @return Collection|array
     */
    public function getInventoryForType(string $type): Collection|array
    {
        return match ($type) {
            'inventory' => $this->fetchCharacterInventory(),
            'usable_sets', 'savable_sets' => $this->characterInventorySetReadService->usableSets($this->character),
            'equipped' => $this->fetchEquipped() ?? [],
            'sets' => [
                'sets' => $this->getCharacterInventorySets(),
                'set_equipped' => $this->character->inventorySets()->where('is_equipped', true)->exists(),
            ],
            'quest_items' => $this->characterInventoryReadService->questItems($this->character),
            'usable_items' => $this->characterUsableInventoryService->usableItems($this->character),
            default => $this->getInventoryForApi(),
        };
    }

    /**
     * Resolve an owned normal Inventory or Inventory Set slot.
     *
     * @param Character $character
     * @param int|Item $slotIdOrItem
     * @return InventorySlot|SetSlot|null
     */
    public function getSlotForItemDetails(Character $character, int|Item $slotIdOrItem): InventorySlot|SetSlot|null
    {
        return $this->characterInventorySlotResolver->itemSlot($character, $slotIdOrItem);
    }

    /**
     * Return paginated Inventory Set summaries.
     *
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function getCharacterInventorySets(int $perPage = 10, int $page = 1): array
    {
        return $this->characterInventorySetReadService->inventorySets($this->character, $perPage, $page);
    }

    /**
     * Return paginated Batch Crafting destination sets.
     *
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @return array
     */
    public function getPaginatedInventorySetOptions(int $perPage = 10, int $page = 1, string $search = ''): array
    {
        return $this->characterInventorySetReadService->paginatedDestinationOptions($this->character, $perPage, $page, $search);
    }

    /**
     * Return paginated Holy Oil target sets.
     *
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @return array
     */
    public function getPaginatedHolyOilTargetSetOptions(int $perPage = 10, int $page = 1, string $search = ''): array
    {
        return $this->characterInventorySetReadService->paginatedHolyOilTargetOptions($this->character, $perPage, $page, $search);
    }

    /**
     * Resolve a valid Batch Crafting target set.
     *
     * @param int $setId
     * @return InventorySet|null
     */
    public function resolveValidTargetInventorySet(int $setId): ?InventorySet
    {
        return $this->characterInventorySetReadService->resolveValidTarget($this->character, $setId);
    }

    /**
     * Resolve an empty Batch Crafting destination set.
     *
     * @param int $setId
     * @return InventorySet|null
     */
    public function resolveEmptyBatchCraftingDestinationSet(int $setId): ?InventorySet
    {
        return $this->characterInventorySetReadService->resolveEmptyBatchCraftingDestination($this->character, $setId);
    }

    /**
     * Resolve any owned Inventory Set.
     *
     * @param int $setId
     * @return InventorySet|null
     */
    public function findOwnedInventorySet(int $setId): ?InventorySet
    {
        return $this->characterInventorySetReadService->findOwned($this->character, $setId);
    }

    /**
     * Return paginated Items from an owned Inventory Set.
     *
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @param array $filters
     * @return array
     */
    public function getSetItems(int $perPage = 10, int $page = 1, string $search = '', array $filters = []): array
    {
        return $this->characterInventorySetReadService->setItems($this->character, $perPage, $page, $search, $filters);
    }

    /**
     * Return paginated normal Inventory slots.
     *
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @param array $filters
     * @return array
     */
    public function fetchCharacterInventory(int $perPage = 10, int $page = 1, string $searchText = '', array $filters = []): array
    {
        return $this->characterInventoryReadService->fetchInventory($this->character, $perPage, $page, $searchText, $filters);
    }

    /**
     * Return paginated Quest Items.
     *
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @return array
     */
    public function fetchCharacterQuestItems(int $perPage = 10, int $page = 1, string $searchText = ''): array
    {
        return $this->characterInventoryReadService->fetchQuestItems($this->character, $perPage, $page, $searchText);
    }

    /**
     * Return paginated usable Items.
     *
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @param array $filter
     * @return array
     */
    public function fetchCharacterUsableItems(int $perPage = 10, int $page = 1, string $searchText = '', array $filter = []): array
    {
        return $this->characterUsableInventoryService->fetchUsableItems($this->character, $perPage, $page, $searchText, $filter);
    }

    /**
     * Return transformed equipped slots.
     *
     * @return array|null
     */
    public function fetchEquipped(): ?array
    {
        return $this->characterInventoryReadService->equipped($this->character);
    }

    /**
     * Destroy one normal Inventory Item.
     *
     * @param int $itemId
     * @return array
     */
    public function deleteItem(int $itemId): array
    {
        return $this->characterInventoryActionService->deleteItem($this->character, $itemId);
    }

    /**
     * Destroy every eligible normal Inventory Item.
     *
     * @return array
     */
    public function destroyAllItemsInInventory(): array
    {
        $result = $this->characterInventoryActionService->destroyAllItems($this->character);
        $result['inventory'] = $this->fetchCharacterInventory();

        return $result;
    }

    /**
     * Disenchant every eligible normal Inventory Item.
     *
     * @return array
     */
    public function disenchantAllItemsInInventory(): array
    {
        return $this->characterInventoryActionService->disenchantAllInventoryItems($this->character);
    }

    /**
     * Unequip one Inventory Item.
     *
     * @param int $inventorySlotId
     * @return array
     */
    public function unequipItem(int $inventorySlotId): array
    {
        return $this->characterInventoryEquipmentService->unequipItem($this->character, $inventorySlotId);
    }

    /**
     * Unequip every Inventory Item.
     *
     * @return array
     */
    public function unequipAllItems(): array
    {
        return $this->characterInventoryEquipmentService->unequipAll($this->character);
    }

    /**
     * Destroy one Alchemy Bag Item.
     *
     * @param int $slotId
     * @return array
     */
    public function destroyAlchemyItem(int $slotId): array
    {
        return $this->withUsableItems($this->characterInventoryActionService->destroyAlchemyItem($this->character, $slotId));
    }

    /**
     * Destroy all eligible Alchemy Bag Items.
     *
     * @return array
     */
    public function destroyAllAlchemyItems(): array
    {
        return $this->withUsableItems($this->characterInventoryActionService->destroyAllAlchemyItems($this->character));
    }

    /**
     * Sell one normal Inventory Item.
     *
     * @param int $itemId
     * @return array
     */
    public function sellItem(int $itemId): array
    {
        return $this->characterInventoryActionService->sellItem($this->character, $itemId);
    }

    /**
     * Disenchant one normal Inventory Item.
     *
     * @param int $itemId
     * @return array
     */
    public function disenchantItem(int $itemId): array
    {
        return $this->characterInventoryActionService->disenchantItem($this->character, $itemId);
    }

    /**
     * Attach refreshed usable Items to a successful action response.
     *
     * @param array $result
     * @return array
     */
    private function withUsableItems(array $result): array
    {
        if (($result['status'] ?? null) === 200) {
            $result['inventory'] = [
                'usable_items' => $this->characterUsableInventoryService->usableItems($this->character),
            ];
        }

        return $result;
    }
}
