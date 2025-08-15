<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Items\Enricher\ItemEnricherFactory;
use App\Flare\Items\Transformers\EquippableItemTransformer;
use App\Flare\Items\Transformers\QuestItemTransformer;
use App\Flare\Items\Values\ItemType;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\InventoryTransformer;
use App\Flare\Transformers\UsableItemTransformer;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as LeagueCollection;

class CharacterInventoryService
{
    use ResponseBuilder;

    /**
     * @var Character
     */
    private Character $character;

    /**
     * @var InventorySlot
     */
    private InventorySlot $inventorySlot;

    /**
     * @var Collection
     */
    private Collection $inventory;

    /**
     * @var array
     */
    private array $positions;

    /**
     * @var bool
     */
    private bool $isInventorySetIsEquipped = false;

    /**
     * @var string
     */
    private string $inventorySetEquippedName = '';

    /**
     * @param ItemEnricherFactory $itemEnricherFactory
     * @param EquippableItemTransformer $equippableItemTransformer
     * @param QuestItemTransformer $questItemTransformer
     * @param UsableItemTransformer $usableItemTransformer
     * @param InventoryTransformer $inventoryTransformer
     * @param MassDisenchantService $massDisenchantService
     * @param UpdateCharacterSkillsService $updateCharacterSkillsService
     * @param UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler
     * @param Pagination $pagination
     * @param Manager $manager
     */
    public function __construct(
        private readonly ItemEnricherFactory    $itemEnricherFactory,
        private readonly EquippableItemTransformer $equippableItemTransformer,
        private readonly QuestItemTransformer $questItemTransformer,
        private readonly UsableItemTransformer $usableItemTransformer,
        private readonly InventoryTransformer $inventoryTransformer,
        private readonly MassDisenchantService $massDisenchantService,
        private readonly UpdateCharacterSkillsService $updateCharacterSkillsService,
        private readonly UpdateCharacterAttackTypesHandler $updateCharacterAttackTypesHandler,
        private readonly Pagination $pagination,
        private readonly Manager $manager,

    ) {}

    /**
     * Set the character
     */
    public function setCharacter(Character $character): CharacterInventoryService
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Set the inventory slot
     */
    public function setInventorySlot(InventorySlot $inventorySlot): CharacterInventoryService
    {
        $this->inventorySlot = $inventorySlot;

        return $this;
    }

    /**
     * Set the positions
     */
    public function setPositions(array $positions): CharacterInventoryService
    {
        $this->positions = $positions;

        return $this;
    }

    /**
     * Get api response.
     */
    public function getInventoryForApi(): array
    {
        $equipped = $this->fetchEquipped();
        $usableSets = $this->getUsableSets();

        return [
            'inventory' => $this->fetchCharacterInventory(),
            'usable_sets' => $usableSets,
            'savable_sets' => $usableSets,
            'equipped' => ! is_null($equipped) ? $equipped : [],
            'sets' => $this->getCharacterInventorySets(),
            'quest_items' => $this->getQuestItems(),
            'usable_items' => $this->getUsableItems(),
            'set_is_equipped' => $this->isInventorySetIsEquipped,
            'set_name_equipped' => $this->inventorySetEquippedName,
        ];
    }

    public function getInventoryForType(string $type): Collection|array
    {
        switch ($type) {
            case 'inventory':
                return $this->fetchCharacterInventory();
            case 'usable_sets':
            case 'savable_sets':
                return $this->getUsableSets();
            case 'equipped':
                $equipped = $this->fetchEquipped();

                return ! is_null($equipped) ? $equipped : [];
            case 'sets':
                return [
                    'sets' => $this->getCharacterInventorySets(),
                    'set_equipped' => InventorySet::where('character_id', $this->character->id)->where('is_equipped', true)->count() > 0,
                ];
            case 'quest_items':
                return $this->getQuestItems();
            case 'usable_items':
                return $this->getUsableItems();
            default:
                return $this->getInventoryForApi();
        }
    }

    public function getSlotForItemDetails(Character $character, Item $item): InventorySlot|SetSlot|null
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        if ($inventory) {
            $slot = $inventory->slots()->where('item_id', $item->id)->first();
            if ($slot) {
                return $slot;
            }
        }

        $desiredSet = $character->inventorySets()
            ->whereHas('slots', function ($query) use ($item) {
                $query->where('item_id', $item->id);
            })->first();

        if (!$desiredSet) {
            return null;
        }

        return $desiredSet->slots->first(function ($slot) use ($item) {
            return (int)$slot->item_id === (int)$item->id;
        });
    }

    /**
     * Disenchant all items in an inventory.
     */
    public function disenchantAllItems(Collection $slots, Character $character): array
    {

        $maxedOutGoldDust = $character->gold_dust >= MaxCurrenciesValue::MAX_GOLD_DUST;

        $this->massDisenchantService->setUp($character)->disenchantItems($slots);

        $totalDisenchantingLevels = $this->massDisenchantService->getDisenchantingTimesLeveled();
        $totalEnchantingLevels = $this->massDisenchantService->getEnchantingTimesLeveled();
        $totalGoldDust = $this->massDisenchantService->getTotalGoldDust();

        $this->updateCharacterSkillsService->updateCharacterCraftingSkills($character->refresh());

        $message = 'Disenchanted all items and gained: ' . ($maxedOutGoldDust ? 0 . ' (You are capped ) ' : number_format($totalGoldDust)) . ' Gold Dust (with gold dust rushes)';

        if ($totalDisenchantingLevels > 0) {
            $message .= ' You also gained: ' . $totalDisenchantingLevels . ' Skill Levels in Disenchanting.';
        }

        if ($totalEnchantingLevels > 0) {
            $message .= ' You also gained: ' . $totalEnchantingLevels . ' Skill Levels in Enchanting.';
        }

        return $this->successResult([
            'message' => $message,
        ]);
    }

    /**
     * Get character inventory sets.
     */
    public function getCharacterInventorySets(int $perPage = 10, int $page = 1): array
    {
        $sets = [];

        foreach ($this->character->inventorySets as $index => $inventorySet) {

            if (is_null($inventorySet->name)) {
                $sets[] = [
                    'name' => 'Set ' . $index + 1,
                    'equippable' => $inventorySet->can_be_equipped,
                    'set_id' => $inventorySet->id,
                    'equipped' => $inventorySet->is_equipped,
                ];
            } else {
                $sets[] = [
                    'name' => $inventorySet->name,
                    'equippable' => $inventorySet->can_be_equipped,
                    'set_id' => $inventorySet->id,
                    'equipped' => $inventorySet->is_equipped,
                ];
            }
        }

        $setCollection = collect($sets);

        return $this->pagination->paginateCollectionResponse($setCollection, $perPage, $page);
    }

    public function getSetItems(int $perPage = 10, int $page = 1, string $search = '', array $filters = []): array {
        $sets = $this->character->inventorySets();

        if (isset($filters['set_id'])) {
            $set = $sets->where('id', $filters['set_id'])->first();
        } else {
            $set = $sets->first();
        }

        $slots = $set->slots;

        if (!empty($search)) {
            $slots = $slots->filter(function ($slot) use ($search) {
                $item = $slot->item;

                return Str::contains(Str::lower($item->name), Str::lower($search)) ||
                    ($item->itemPrefix && Str::contains(Str::lower($item->itemPrefix->name), Str::lower($search))) ||
                    ($item->itemSuffix && Str::contains(Str::lower($item->itemSuffix->name), Str::lower($search)));
            });
        }

        $items = $slots->map(function($slot) {
            return $this->itemEnricherFactory->buildItem($slot->item);
        });

        return $this->pagination->buildPaginatedDate($items, $this->equippableItemTransformer, $perPage, $page);
    }

    /**
     * Get equipped inventory set name.
     *
     * - Either null if none.
     * - Equipped set name.
     * - Equipped set string id + 1
     */
    public function getEquippedInventorySetName(): ?string
    {
        $equippedSet = $this->character->inventorySets()->where('is_equipped', true)->first();

        if (is_null($equippedSet)) {
            return null;
        }

        if (! is_null($equippedSet->name)) {
            return $equippedSet->name;
        }

        return 'Set ' . $this->character->inventorySets->search(function ($set) use ($equippedSet) {
            return $set->id === $equippedSet->id;
        }) + 1;
    }

    /**
     * Returns the usable items.
     *
     * @param string $searchText
     * @param array $filters
     * @return Collection
     */
    public function getUsableItems(string $searchText = '', array $filters = []): Collection
    {
        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $query = InventorySlot::where('inventory_slots.inventory_id', $inventory->id)
            ->join('items', function ($join) use ($searchText, $filters) {
                $join->on('inventory_slots.item_id', '=', 'items.id')
                    ->where('items.type', 'alchemy');

                if (!empty($searchText)) {
                    $join->where('items.name', 'like', '%' . $searchText . '%');
                }

                if (isset($filters['increase-stats'])) {
                    $join->whereNotNull('items.increase_stat_by');
                }

                if (isset($filters['effects-skills'])) {
                    $join->whereNotNull('items.increase_skill_bonus_by')
                         ->whereNotNull('items.increase_skill_training_bonus_by');
                }

                if (isset($filters['effects-base-modifiers'])) {
                    $join->whereNotNull('items.base_damage_mod')
                        ->orWhereNotNull('items.base_healing_mod')
                        ->orWhereNotNull('items.base_attack_mod');
                }

                if (isset($filters['damages-kingdoms'])) {
                    $join->where('items.damages_kingdoms', true);
                }

                if (isset($filters['holy-oils'])) {
                    $join->where('items.can_use_on_other_items', true);
                }
            });

        return $query->select('inventory_slots.*')->get();
    }

    /**
     * Returns the quest items.
     *
     * @param string $searchText
     * @return Collection
     */
    public function getQuestItems(string $searchText = ''): Collection
    {
        $slots = $this->character->inventory->slots->where('item.type', 'quest');

        if ($searchText !== '') {
            $slots = $slots->filter(function ($slot) use ($searchText) {
                return Str::contains(Str::lower($slot->item->name), $searchText);
            });
        }

        return $slots->map(function($slot) {
            return $slot->item;
        });
    }


    /**
     * Gets a list of usable slot's
     *
     * We return the index + 1 which refers to the slot number.
     * ie, index of 0, is Slot 1 and so on.
     */
    public function getUsableSets(): array
    {
        $ids = InventorySet::where('is_equipped', false)->where('character_id', $this->character->id)->pluck('id')->toArray();
        $setIds = InventorySet::where('character_id', $this->character->id)->pluck('id')->toArray();

        $indexes = [];

        foreach ($ids as $id) {
            $inventorySet = InventorySet::find($id);

            $indexes[] = [
                'index' => array_search($id, $setIds) + 1,
                'id' => $id,
                'name' => is_null($inventorySet->name) ? 'Set ' . array_search($id, $setIds) + 1 : $inventorySet->name,
                'equipped' => false,
            ];
        }

        return $indexes;
    }

    /**
     * Get inventory collection.
     *
     *  - Does not include equipped, usable or quest items.
     *  - Only comes from inventory, does not include sets.
     *  - If the character is currently disenchanting selected items, do not get those items.
     *
     * @param string $searchText
     * @return Collection
     */
    public function getInventorySlotsCollection(string $searchText = ''): Collection
    {
        $slotsToIgnore = Cache::get('character-slots-to-disenchant-' . $this->character->id, []);

        $slots = $this->character
            ->inventory
            ->slots
            ->whereNotIn('item.type', ['quest', 'alchemy'])
            ->whereNotIn('id', $slotsToIgnore)
            ->where('equipped', false);

        if ($searchText !== '') {
            $search = Str::lower($searchText);

            $slots = $slots->filter(function ($slot) use ($search) {
                $item = $slot->item;

                return Str::contains(Str::lower($item->name), $search)
                    || ($item->itemPrefix && Str::contains(Str::lower($item->itemPrefix->name), $search))
                    || ($item->itemSuffix && Str::contains(Str::lower($item->itemSuffix->name), $search));
            });
        }

        return $slots->values();
    }

    public function getInventoryCollection(string $searchText = ''): \Illuminate\Support\Collection
    {
        return $this->getInventorySlotsCollection($searchText)
            ->map(function ($slot) {
                $slot->item = $this->itemEnricherFactory->buildItem($slot->item, $this->character->damage_stat);
                return $slot->item;
            })
            ->sortByDesc(fn($item) => (float) $item->total_damage_stat_bonus)
            ->values();
    }

    /**
     * Fetches the characters inventory.
     *
     * - Does not include equipped, usable or quest items.
     * - Only comes from inventory, does not include sets.
     *
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @return array
     */
    public function fetchCharacterInventory(int $perPage = 10, int $page = 1, string $searchText = ''): array
    {
        $slots = $this->getInventoryCollection($searchText);

        return $this->pagination->buildPaginatedDate($slots, $this->equippableItemTransformer, $perPage, $page);
    }

    /**
     * Returns all quest items - paginated.
     *
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @return array
     */
    public function fetchCharacterQuestItems(int $perPage = 10, int $page = 1, string $searchText = ''): array {
        $items = $this->getQuestItems($searchText);

        return $this->pagination->buildPaginatedDate($items, $this->questItemTransformer, $perPage, $page);
    }

    /**
     * Returns all usable items - paginated
     *
     * @param int $perPage
     * @param int $page
     * @param string $searchText
     * @param array $filter
     * @return array
     */
    public function fetchCharacterUsableItems(int $perPage = 10, int $page = 1, string $searchText = '', array $filter = []): array {
        $slots = $this->getUsableItems($searchText, $filter);

        return $this->pagination->buildPaginatedDate($slots, $this->usableItemTransformer, $perPage, $page);
    }

    /**
     * Fetch inventory slot items.
     *
     * - Does not include alchemy or quest items.
     * - Items can also not be equipped.
     */
    public function findCharacterInventorySlotIds(): array
    {

        return $this->character
            ->inventory
            ->slots
            ->whereNotIn('item.type', ['quest', 'alchemy', 'artifact'])
            ->where('equipped', false)
            ->sortBy('id')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Fetch equipped items.
     */
    public function fetchEquipped(): array
    {

        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $items = InventorySlot::query()
            ->where('inventory_id', $inventory->id)
            ->where('equipped', true)
            ->with('item')
            ->get()
            ->pluck('item')
            ->filter()
            ->map(fn ($item) => $this->itemEnricherFactory->buildItem($item))
            ->values();

        if ($items->isNotEmpty()) {
            $items = new LeagueCollection($items, $this->equippableItemTransformer);

            return $this->manager->createData($items)->toArray();
        }

        $inventorySet = InventorySet::where('character_id', $this->character->id)->where('is_equipped', true)->first();

        if (is_null($inventorySet)) {
            return [];
        }

        $this->isInventorySetIsEquipped = true;

        if (! is_null($inventorySet->name)) {
            $this->inventorySetEquippedName = $inventorySet->name;
        } else {
            $index = $this->character->inventorySets->search(function ($set) use ($inventorySet) {
                return $set->id === $inventorySet->id;
            });

            if ($index !== false) {
                $this->inventorySetEquippedName = 'Set ' . $index + 1;
            }
        }

        $items = SetSlot::query()
            ->where('inventory_set_id', $inventorySet->id)
            ->with('item')
            ->get()
            ->pluck('item')
            ->filter()
            ->map(fn ($item) => $this->itemEnricherFactory->buildItem($item))
            ->values();

        $items = new LeagueCollection($items, $this->equippableItemTransformer);

        return $this->manager->createData($items)->toArray();
    }

    /**
     * Set the inventory
     */
    public function setInventory(): CharacterInventoryService
    {
        $this->inventory = $this->getInventory();

        return $this;
    }

    /**
     * Get inventory
     */
    protected function getInventory(): Collection
    {

        $inventory = $this->character->inventory->slots()->whereIn('position', $this->positions)->get();

        if (! $inventory->isEmpty()) {
            return $inventory;
        }

        $result = $this->character->inventorySets()
            ->where('is_equipped', true)
            ->whereHas('slots', function ($query) {
                $query->whereIn('position', $this->positions);
            })
            ->get()
            ->pluck('slots')
            ->flatten();

        return new Collection($result);
    }

    /**
     * Return the inventory
     */
    public function inventory(): Collection
    {
        return $this->inventory;
    }

    /**
     * Fetches the type of the item.
     *
     * @throws Exception
     */
    public function getType(Item $item): string
    {
        return $this->fetchType($item->type);
    }

    /**
     * Delete an item from the inventory.
     */
    public function deleteItem(int $slotId): array
    {
        $slot = $this->character->inventory->slots->filter(function ($slot) use ($slotId) {
            return $slot->id === $slotId;
        })->first();

        if (is_null($slot)) {
            return $this->errorResult('You don\'t own that item.');
        }

        if ($slot->equipped) {
            return $this->errorResult('Cannot destroy equipped item.');
        }

        $name = $slot->item->affix_name;

        $item = null;

        if ($slot->item->type === 'artifact' && $slot->item->itemSkillProgressions->isNotEmpty()) {
            $item = $slot->item;
        }

        $slot->delete();

        if (! is_null($item)) {
            $item->itemSkillProgressions()->delete();

            $item->delete();
        }

        $this->character = $this->character->refresh();

        event(new UpdateCharacterInventoryCountEvent($this->character));

        return $this->successResult([
            'message' => 'Destroyed ' . $name . '.',
            'inventory' => [
                'inventory' => $this->getInventoryForType('inventory'),
            ],
        ]);
    }

    /**
     * Destroy all items in your inventory.
     *
     * - Will not destroy sets or items in sets.
     * - Will not destroy quest items or usable items.
     * - Will not destroy artifact items either.
     */
    public function destroyAllItemsInInventory(): array
    {
        $slotIds = $this->findCharacterInventorySlotIds();

        $this->character->inventory->slots()->whereIn('id', $slotIds)->delete();

        $character = $this->character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message'   => 'Destroyed all items.',
            'inventory' => $this->getInventoryCollection(),
        ]);
    }

    public function disenchantAllItemsInInventory(): array
    {
        // Work with SLOTS, not items.
        $slots = $this->character->inventory->slots
            ->where('equipped', false)
            ->filter(function ($slot) {
                // Slot may be dangling (unlikely, but safe guard):
                if (!$slot->item) {
                    return false;
                }

                // Ignore types we never disenchant:
                if (in_array($slot->item->type, ['quest', 'alchemy', 'artifact'], true)) {
                    return false;
                }

                // Only disenchant items that actually have an affix:
                return !is_null($slot->item->item_prefix_id) || !is_null($slot->item->item_suffix_id);
            })
            ->values();

        if ($slots->isEmpty()) {
            return $this->successResult([
                'message' => 'You have nothing to disenchant.',
            ]);
        }

        // Mass disenchanter expects a collection of SLOTS.
        return $this->disenchantAllItems($slots, $this->character);
    }

    /**
     * Unequip an item from the player.
     *
     * @throws Exception
     */
    public function unequipItem(int $inventorySlotId): array
    {
        if ($this->character->isInventoryFull()) {

            return $this->errorResult('Your inventory is full. Cannot unequip items. You have no room in your inventory.');
        }

        $foundItem = $this->character->inventory->slots->find($inventorySlotId);

        if (is_null($foundItem)) {
            return $this->errorResult('No item found to be unequipped.');
        }

        $foundItem->update([
            'equipped' => false,
            'position' => null,
        ]);

        $character = $this->character->refresh();

        $this->updateCharacterAttackDataCache($character);

        event(new UpdateTopBarEvent($character->refresh()));

        return $this->successResult([
            'message' => 'Unequipped item: ' . $foundItem->item->affix_name,
            'inventory' => [
                'inventory' => $this->getInventoryForType('inventory'),
                'equipped' => $this->getInventoryForType('equipped'),
                'sets' => $this->getInventoryForType('sets')['sets'],
                'set_is_equipped' => false,
                'set_name_equipped' => $this->getEquippedInventorySetName(),
                'usable_sets' => $this->getUsableSets(),
            ],
        ]);
    }

    /**
     * Unequip all items.
     *
     * @throws Exception
     */
    public function unequipAllItems(): array
    {
        if ($this->character->isInventoryFull()) {
            return $this->errorResult('Your inventory is full. Cannot unequip items. You have no room in your inventory.');
        }

        $this->character->inventory->slots->each(function ($slot) {
            $slot->update([
                'equipped' => false,
                'position' => null,
            ]);
        });

        $character = $this->character->refresh();

        $this->updateCharacterAttackDataCache($character);

        return $this->successResult([
            'message' => 'All items have been unequipped.',
            'inventory' => [
                'inventory' => $this->getInventoryForType('inventory'),
                'equipped' => $this->getInventoryForType('equipped'),
                'set_is_equipped' => false,
                'set_name_equipped' => $this->getEquippedInventorySetName(),
                'sets' => $this->getInventoryForType('sets')['sets'],
                'usable_sets' => $this->getUsableSets(),
            ],
        ]);
    }

    /**
     * Destroy Alchemy item.
     */
    public function destroyAlchemyItem(int $slotId): array
    {
        $slot = $this->character->inventory->slots->filter(function ($slot) use ($slotId) {
            return $slot->id === $slotId;
        })->first();

        if (is_null($slot)) {

            return $this->errorResult('No alchemy item found to destroy.');
        }

        $name = $slot->item->affix_name;

        $slot->delete();

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Destroyed Alchemy Item: ' . $name . '.',
            'inventory' => [
                'usable_items' => $this->getInventoryForType('usable_items'),
            ],
        ]);
    }

    /**
     * Destroy all alchemy items.
     */
    public function destroyAllAlchemyItems(): array
    {
        $slots = $this->character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'alchemy';
        });

        foreach ($slots as $slot) {
            $slot->delete();
        }

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));

        return $this->successResult([
            'message' => 'Destroyed All Alchemy Items.',
            'inventory' => [
                'usable_items' => $this->getInventoryForType('usable_items'),
            ],
        ]);
    }

    /**
     * Updates the character stats.
     *
     * @throws Exception
     */
    protected function updateCharacterAttackDataCache(Character $character): void
    {
        $this->updateCharacterAttackTypesHandler->updateCache($character);
    }

    /**
     * Fetch type based on accepted types.
     *
     * @throws Exception
     */
    protected function fetchType(string $type): string
    {

        if (in_array($type, ArmourTypes::armourTypes())) {
            $type = 'armour';
        }

        $acceptedTypes = [
            ...ItemType::validWeapons(),
            'ring',
            'shield',
            'artifact',
            'spell',
            'armour',
            'alchemy',
            'quest',
        ];

        // Spells do not have the tye spell - they are differentiated by damage or healing suffix.
        if ($type === 'spell-damage' || $type === 'spell-healing') {
            $type = 'spell';
        }

        if (!in_array($type, $acceptedTypes)) {
            throw new Exception('Unknown Item type: ' . $type);
        }

        return $type;
    }
}
