<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Pagination\Pagination;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterInventory\Transformers\InventorySetOptionTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventoryTransformer;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Core\Items\Values\ArmourType;
use App\Game\Core\Items\Values\ItemType;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Skills\Services\DisenchantService;
use App\Game\Skills\Services\MassDisenchantService;
use App\Game\Skills\Services\UpdateCharacterSkillsService;
use Exception;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as LeagueCollection;

class CharacterInventoryService
{
    use ResponseBuilder;

    private Character $character;

    private InventorySlot $inventorySlot;

    private Collection $inventory;

    private array $positions;

    private bool $isInventorySetIsEquipped = false;

    private ?string $inventorySetEquippedName = null;

    public function __construct(
        private readonly ItemEnricherFactory $itemEnricherFactory,
        private readonly EquippableItemTransformer $equippableItemTransformer,
        private readonly QuestItemTransformer $questItemTransformer,
        private readonly UsableItemTransformer $usableItemTransformer,
        private readonly InventoryTransformer $inventoryTransformer,
        private readonly InventorySetService $inventorySetService,
        private readonly MassDisenchantService $massDisenchantService,
        private readonly UpdateCharacterSkillsService $updateCharacterSkillsService,
        private readonly DisenchantService $disenchantService,
        private readonly Pagination $pagination,
        private readonly Manager $manager,
        private readonly InventorySetOptionTransformer $inventorySetOptionTransformer,
    ) {}

    /**
     * Set the character used for subsequent inventory operations.
     *
     * @param  Character  $character  The character to operate on.
     */
    public function setCharacter(Character $character): CharacterInventoryService
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Set the inventory slot used for subsequent inventory operations.
     *
     * @param  InventorySlot  $inventorySlot  The inventory slot to operate on.
     */
    public function setInventorySlot(InventorySlot $inventorySlot): CharacterInventoryService
    {
        $this->inventorySlot = $inventorySlot;

        return $this;
    }

    /**
     * Set the inventory slot positions used to resolve the character's inventory.
     *
     * @param  array  $positions  The slot positions to resolve inventory from.
     */
    public function setPositions(array $positions): CharacterInventoryService
    {
        $this->positions = $positions;

        return $this;
    }

    /**
     * Build the complete inventory API payload for the character.
     *
     * @return array The full inventory, sets, quest items, usable items, and equipped-set payload.
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

    /**
     * Return the currently equipped Inventory Set's name, when a set is equipped.
     *
     * @return string|null The equipped Inventory Set's name, or null when no set is equipped.
     */
    public function getSetName(): ?string
    {
        return $this->inventorySetEquippedName;
    }

    /**
     * Return the character's inventory data for the requested inventory panel type.
     *
     * @param  string  $type  The requested inventory panel type.
     * @return Collection|array The resolved inventory data for the requested type.
     */
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

    /**
     * Resolves an exact SetSlot for item details, instead of the item_id-based
     * lookup in getSlotForItemDetails() below, which picks the first slot it finds
     * matching that item_id. When duplicate, non-enchanted items share the same
     * catalog Item row across multiple SetSlots (e.g. several identical Batch
     * Crafting outputs kept in the Crafted Items Set), that ambiguous lookup can
     * resolve to the wrong physical slot. Callers that already know the specific
     * SetSlot id (such as Batch Crafting action history) should use this instead.
     *
     * @param  Character  $character  The owning character.
     * @param  Item  $item  The catalog item the slot must contain.
     * @param  int  $setSlotId  The exact SetSlot id to resolve.
     * @return SetSlot|null The matching SetSlot, or null when it does not exist for the character.
     */
    public function getSetSlotForItemDetails(Character $character, Item $item, int $setSlotId): ?SetSlot
    {
        return SetSlot::where('id', $setSlotId)
            ->where('item_id', $item->id)
            ->whereHas('inventorySet', fn ($query) => $query->where('character_id', $character->id))
            ->first();
    }

    /**
     * Resolve the inventory or set slot holding the given item, for item detail display.
     *
     * @param  Character  $character  The owning character.
     * @param  int|Item  $slotIdOrItem  The slot id or item to resolve a slot for.
     * @return InventorySlot|SetSlot|null The resolved slot, or null when none is found.
     */
    public function getSlotForItemDetails(Character $character, int|Item $slotIdOrItem): InventorySlot|SetSlot|null
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        if ($inventory) {
            $slot = $inventory->slots()
                ->when($slotIdOrItem instanceof Item, fn ($q) => $q->where('item_id', $slotIdOrItem->id))
                ->when(! ($slotIdOrItem instanceof Item), fn ($q) => $q->where('id', $slotIdOrItem))
                ->first();

            if ($slot) {
                return $slot;
            }
        }

        return SetSlot::query()
            ->join('inventory_sets', 'inventory_sets.id', '=', 'set_slots.inventory_set_id')
            ->where('inventory_sets.character_id', $character->id)
            ->when($slotIdOrItem instanceof Item, fn ($q) => $q->where('set_slots.item_id', $slotIdOrItem->id))
            ->when(! ($slotIdOrItem instanceof Item), fn ($q) => $q->where('set_slots.id', $slotIdOrItem))
            ->select('set_slots.*')
            ->first();
    }

    /**
     * Disenchant all items in an inventory.
     *
     * @param  Collection  $slots  The slots to disenchant.
     * @param  Character  $character  The character disenchanting the items.
     * @return array The disenchant-all result.
     */
    public function disenchantAllItems(Collection $slots, Character $character): array
    {
        $maxedOutGoldDust = $character->gold_dust >= CurrencyLimit::MAX_GOLD_DUST;

        $this->massDisenchantService->setUp($character)->disenchantItems($slots);

        $totalDisenchantingLevels = $this->massDisenchantService->getDisenchantingTimesLeveled();
        $totalEnchantingLevels = $this->massDisenchantService->getEnchantingTimesLeveled();
        $totalGoldDust = $this->massDisenchantService->getTotalGoldDust();

        $this->updateCharacterSkillsService->updateCharacterCraftingSkills($character->refresh());

        $message = 'Disenchanted all items and gained: '.($maxedOutGoldDust ? 0 .' (You are capped ) ' : number_format($totalGoldDust)).' Gold Dust (with gold dust rushes)';

        if ($totalDisenchantingLevels > 0) {
            $message .= ' You also gained: '.$totalDisenchantingLevels.' Skill Levels in Disenchanting.';
        }

        if ($totalEnchantingLevels > 0) {
            $message .= ' You also gained: '.$totalEnchantingLevels.' Skill Levels in Enchanting.';
        }

        return $this->successResult([
            'message' => $message,
        ]);
    }

    /**
     * Get character inventory sets.
     *
     * @param  int  $perPage  The number of sets to return per page.
     * @param  int  $page  The page number to return.
     * @return array The paginated inventory set payload.
     */
    public function getCharacterInventorySets(int $perPage = 10, int $page = 1): array
    {
        $sets = [];
        $inventorySets = $this->character->inventorySets()
            ->with('slots.item')
            ->orderByRaw('case when special_type = ? then 1 else 0 end', [InventorySet::BATCH_CRAFTING_SPECIAL_TYPE])
            ->orderBy('id')
            ->get();

        foreach ($inventorySets as $index => $inventorySet) {
            $inventorySet->slots->each(function (SetSlot $slot): void {
                $slot->setRelation('item', $this->itemEnricherFactory->buildItem($slot->item));
            });

            $slots = new LeagueCollection($inventorySet->slots, $this->inventoryTransformer);
            $slotCount = $inventorySet->currentSlotCount();
            $remainingInventorySpace = max(0, $this->character->inventory_max - $this->character->getInventoryCount());
            $payload = [
                'name' => $inventorySet->name ?? 'Set '.($index + 1),
                'items' => array_reverse($this->manager->createData($slots)->toArray()),
                'equippable' => $inventorySet->can_be_equipped,
                'set_id' => $inventorySet->id,
                'equipped' => $inventorySet->is_equipped,
                'is_equippable' => $this->inventorySetService->isSetEquippable($inventorySet),
                'is_batch_crafting_set' => $inventorySet->isBatchCraftingSet(),
                'max_slots' => $inventorySet->max_slots,
                'current_slots' => $slotCount,
                'remaining_slots' => $inventorySet->remainingSlots(),
                'can_empty' => $slotCount <= $remainingInventorySpace,
                'empty_disabled_reason' => $slotCount > $remainingInventorySpace ? 'Your inventory does not have enough room to empty this set.' : null,
            ];

            $sets[] = $payload;
        }

        $setCollection = collect($sets);

        return $this->pagination->paginateCollectionResponse($setCollection, $perPage, $page);
    }

    /**
     * Build the lean, database-paginated, searchable normal Inventory Set destination options for a Batch Crafting Craft Set output selector.
     *
     * Only empty, unequipped, normal Inventory Sets are eligible. The special Crafted Items/Batch Crafting Set is excluded here
     * because it has its own dedicated output destination. Each eligible set carries its real ordinal position among the
     * character's normal Inventory Sets ordered by id, so fallback display names stay stable regardless of which sets are eligible.
     *
     * @param  int  $perPage  The number of sets to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $search  The optional search text to filter sets by name.
     * @return array The paginated, lean, eligible Inventory Set option payload.
     */
    public function getPaginatedInventorySetOptions(int $perPage = 10, int $page = 1, string $search = ''): array
    {
        $query = $this->character->inventorySets()
            ->select('inventory_sets.*')
            ->selectSub(
                fn (QueryBuilder $query) => $query
                    ->selectRaw('count(*) + 1')
                    ->from('inventory_sets as numbered_inventory_sets')
                    ->whereColumn('numbered_inventory_sets.character_id', 'inventory_sets.character_id')
                    ->whereColumn('numbered_inventory_sets.id', '<', 'inventory_sets.id')
                    ->where(fn ($query) => $query->whereNull('numbered_inventory_sets.special_type')
                        ->orWhere('numbered_inventory_sets.special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)),
                'set_number',
            )
            ->withCount('slots')
            ->where('is_equipped', false)
            ->where(fn ($query) => $query->whereNull('special_type')
                ->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE))
            ->whereDoesntHave('slots')
            ->orderBy('id');

        if ($search !== '') {
            $query->where('name', 'LIKE', '%'.$search.'%');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->inventorySetOptionTransformer);
    }

    /**
     * Return the character's paginated selectable Inventory Sets that contain at least one
     * currently eligible Holy Oil target item.
     *
     * Unlike getPaginatedInventorySetOptions(), a Set does not need to be empty to appear here.
     *
     * @param  int  $perPage  The number of sets to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $search  The optional search text to filter sets by name.
     * @return array The paginated Holy Oil target set option payload.
     */
    public function getPaginatedHolyOilTargetSetOptions(int $perPage = 10, int $page = 1, string $search = ''): array
    {
        $query = $this->character->inventorySets()
            ->select('inventory_sets.*')
            ->selectSub(
                fn (QueryBuilder $query) => $query
                    ->selectRaw('count(*) + 1')
                    ->from('inventory_sets as numbered_inventory_sets')
                    ->whereColumn('numbered_inventory_sets.character_id', 'inventory_sets.character_id')
                    ->whereColumn('numbered_inventory_sets.id', '<', 'inventory_sets.id')
                    ->where(fn ($query) => $query->whereNull('numbered_inventory_sets.special_type')
                        ->orWhere('numbered_inventory_sets.special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)),
                'set_number',
            )
            ->withCount('slots')
            ->where('is_equipped', false)
            ->where(fn ($query) => $query->whereNull('special_type')
                ->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE))
            ->whereHas('slots', function ($slotQuery) {
                $slotQuery->whereHas('item', function ($itemQuery) {
                    $itemQuery->whereNotIn('type', ['trinket', 'artifact'])
                        ->whereRaw('holy_stacks > (select count(*) from holy_stacks where holy_stacks.item_id = items.id)');
                });
            })
            ->orderBy('id');

        if ($search !== '') {
            $query->where('name', 'LIKE', '%'.$search.'%');
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->inventorySetOptionTransformer);
    }

    /**
     * Resolve a valid normal Inventory Set target belonging to the character, for a Batch Crafting destination.
     *
     * @param  int  $setId  The requested destination Inventory Set id.
     * @return InventorySet|null The valid target set, or null when it is not a legal target.
     */
    public function resolveValidTargetInventorySet(int $setId): ?InventorySet
    {
        $set = $this->character->inventorySets()->find($setId);

        if (is_null($set) || $set->is_equipped || $set->isBatchCraftingSet()) {
            return null;
        }

        return $set;
    }

    /**
     * Resolve a normal, unequipped, currently empty Inventory Set belonging to the character, for Batch Crafting start/preview validation only.
     *
     * Unlike resolveValidTargetInventorySet(), which resolves the already-selected destination
     * during runtime placement after a Batch has begun filling it, this method enforces the
     * empty-at-start contract required before a Batch Crafting run may begin.
     *
     * @param  int  $setId  The requested destination Inventory Set id.
     * @return InventorySet|null The eligible empty set, or null when it is not a legal start destination.
     */
    public function resolveEmptyBatchCraftingDestinationSet(int $setId): ?InventorySet
    {
        return $this->character->inventorySets()
            ->where('id', $setId)
            ->where('is_equipped', false)
            ->where(fn ($query) => $query->whereNull('special_type')
                ->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE))
            ->whereDoesntHave('slots')
            ->first();
    }

    /**
     * Resolve an Inventory Set the character genuinely owns, for factual display purposes.
     *
     * Unlike resolveValidTargetInventorySet(), this does not exclude equipped or Batch
     * Crafting Sets, because a previously selected destination set may legitimately have
     * become equipped after a Batch Crafting run started.
     *
     * @param  int  $setId  The requested Inventory Set id.
     * @return InventorySet|null The character's own set, or null when it does not belong to the character.
     */
    public function findOwnedInventorySet(int $setId): ?InventorySet
    {
        return $this->character->inventorySets()->find($setId);
    }

    /**
     * Return the paginated items belonging to one of the character's Inventory Sets.
     *
     * @param  int  $perPage  The number of items to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $search  The optional search text to filter items by name.
     * @param  array  $filters  The optional filters, including a specific set id.
     * @return array The paginated set item payload.
     */
    public function getSetItems(int $perPage = 10, int $page = 1, string $search = '', array $filters = []): array
    {
        $sets = $this->character->inventorySets();

        if (isset($filters['set_id'])) {
            $set = $sets->where('id', $filters['set_id'])->first();
        } else {
            $set = $sets->where('is_equipped', true)->first();
        }

        $slots = $set->slots;

        if (! empty($search)) {
            $slots = $slots->filter(function ($slot) use ($search) {
                $item = $slot->item;

                return Str::contains(Str::lower($item->name), Str::lower($search)) ||
                    ($item->itemPrefix && Str::contains(Str::lower($item->itemPrefix->name), Str::lower($search))) ||
                    ($item->itemSuffix && Str::contains(Str::lower($item->itemSuffix->name), Str::lower($search)));
            });
        }

        $slots = $slots
            ->map(function ($slot) {
                $slot->item = $this->itemEnricherFactory->buildItem($slot->item);

                return $slot;
            })
            ->sortByDesc(fn ($slot) => $slot->item->total_damage_stat_bonus)
            ->values();

        return $this->pagination->buildPaginatedDate($slots, $this->equippableItemTransformer, $perPage, $page);
    }

    /**
     * Get equipped inventory set name.
     *
     * - Either null if none.
     * - Equipped set name.
     * - Equipped set string id + 1
     *
     * @return string|null The equipped set's name, or null when no set is equipped.
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

        return 'Set '.$this->character->inventorySets->search(function ($set) use ($equippedSet) {
            return $set->id === $equippedSet->id;
        }) + 1;
    }

    /**
     * Returns the usable items.
     *
     * @param  string  $searchText  The optional search text to filter items by name.
     * @param  array  $filters  The optional usable-item filters.
     * @return array The usable item payload.
     */
    public function getUsableItems(string $searchText = '', array $filters = []): array
    {
        return $this->getUsableItemsCollection($searchText, $filters)
            ->map(function (AlchemyBagSlot $slot) {
                $item = $this->usableItemTransformer->transform($slot);

                return array_merge($item, [
                    'id' => $slot->id,
                    'amount' => $slot->amount,
                ]);
            })
            ->toArray();
    }

    /**
     * Returns the usable items as an Eloquent collection, filtered by search text and filters.
     *
     * @param  string  $searchText  The optional search text to filter items by name.
     * @param  array  $filters  The optional usable-item filters.
     * @return Collection The filtered usable item slots.
     */
    private function getUsableItemsCollection(string $searchText = '', array $filters = []): Collection
    {
        $alchemyBag = $this->character->alchemyBag;

        if (is_null($alchemyBag)) {
            return new Collection;
        }

        $slots = AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)
            ->where('character_id', $this->character->id)
            ->with('item')
            ->get();

        if ($searchText !== '') {
            $search = Str::lower($searchText);

            $slots = $slots->filter(function (AlchemyBagSlot $slot) use ($search) {
                return Str::contains(Str::lower($slot->item->name), $search);
            });
        }

        if (! empty($filters)) {
            $slots = $slots->filter(function (AlchemyBagSlot $slot) use ($filters) {
                return $this->usableItemMatchesFilters($slot->item, $filters);
            });
        }

        return $slots->values();
    }

    /**
     * Determines whether a usable item matches any of the selected usable-item filters.
     *
     * @param  Item  $item  The item to test.
     * @param  array  $filters  The selected usable-item filters.
     * @return bool Whether the item matches at least one selected filter.
     */
    private function usableItemMatchesFilters(Item $item, array $filters): bool
    {
        if (isset($filters['increase-stats']) && $item->increase_stat_by > 0) {
            return true;
        }

        if (isset($filters['effects-skills']) && (
            $item->increase_skill_bonus_by > 0 ||
            $item->increase_skill_training_bonus_by > 0
        )) {
            return true;
        }

        if (isset($filters['effects-base-modifiers']) && (
            $item->base_damage_mod > 0 ||
            $item->base_ac_mod > 0 ||
            $item->base_healing_mod > 0 ||
            $item->fight_time_out_mod_bonus > 0 ||
            $item->move_time_out_mod_bonus > 0
        )) {
            return true;
        }

        if (isset($filters['damages-kingdoms']) && $item->damages_kingdoms) {
            return true;
        }

        if (isset($filters['holy-oils']) && ! is_null($item->holy_level)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the quest items.
     *
     * @param  string  $searchText  The optional search text to filter items by name.
     * @return Collection The matching quest items.
     */
    public function getQuestItems(string $searchText = ''): Collection
    {
        $slots = $this->character->inventory->slots->where('item.type', 'quest');

        if ($searchText !== '') {
            $slots = $slots->filter(function ($slot) use ($searchText) {
                return Str::contains(Str::lower($slot->item->name), $searchText);
            });
        }

        return $slots->map(function ($slot) {
            return $slot->item;
        });
    }

    /**
     * Gets a list of usable slot's
     *
     * We return the index + 1 which refers to the slot number.
     * ie, index of 0, is Slot 1 and so on.
     *
     * @return array The usable, non-equipped, non-Batch-Crafting inventory sets.
     */
    public function getUsableSets(): array
    {
        $selectableSets = InventorySet::where('is_equipped', false)
            ->where('character_id', $this->character->id)
            ->where(function ($query) {
                $query->whereNull('special_type')
                    ->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE);
            })
            ->get();
        $setIds = InventorySet::where('character_id', $this->character->id)
            ->where(function ($query) {
                $query->whereNull('special_type')
                    ->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE);
            })
            ->pluck('id')
            ->toArray();

        $indexes = [];

        foreach ($selectableSets as $selectableSet) {

            $setIndex = array_search($selectableSet->id, $setIds) + 1;

            $indexes[] = [
                'index' => $setIndex,
                'id' => $selectableSet->id,
                'name' => is_null($selectableSet->name) ? 'Set '.$setIndex : $selectableSet->name,
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
     * @param  string  $searchText  The optional search text to filter items by name.
     * @return Collection The matching inventory slots.
     */
    public function getInventorySlotsCollection(string $searchText = ''): Collection
    {
        $slotsToIgnore = Cache::get('character-slots-to-disenchant-'.$this->character->id, []);

        $slots = $this->character
            ->inventory
            ->slots
            ->whereNotIn('item.type', ['quest', 'alchemy', 'gem'])
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

    /**
     * Return the character's normal inventory, enriched and sorted by total damage stat bonus.
     *
     * @param  string  $searchText  The optional search text to filter items by name.
     * @return Collection The enriched, sorted inventory slots.
     */
    public function getInventoryCollection(string $searchText = ''): Collection
    {
        return $this->getInventorySlotsCollection($searchText)
            ->map(function ($slot) {
                $slot->item = $this->itemEnricherFactory->buildItem($slot->item, $this->character->damage_stat);

                return $slot;
            })
            ->sortByDesc(fn ($slot) => $slot->item->total_damage_stat_bonus)
            ->values();
    }

    /**
     * Fetches the characters inventory.
     *
     * - Does not include equipped, usable or quest items.
     * - Only comes from inventory, does not include sets.
     *
     * @param  int  $perPage  The number of items to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $searchText  The optional search text to filter items by name.
     * @return array The paginated inventory payload.
     */
    public function fetchCharacterInventory(int $perPage = 10, int $page = 1, string $searchText = ''): array
    {
        $slots = $this->getInventoryCollection($searchText);

        return $this->pagination->buildPaginatedDate($slots, $this->equippableItemTransformer, $perPage, $page);
    }

    /**
     * Returns all quest items - paginated.
     *
     * @param  int  $perPage  The number of items to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $searchText  The optional search text to filter items by name.
     * @return array The paginated quest item payload.
     */
    public function fetchCharacterQuestItems(int $perPage = 10, int $page = 1, string $searchText = ''): array
    {
        $items = $this->getQuestItems($searchText);

        return $this->pagination->buildPaginatedDate($items, $this->questItemTransformer, $perPage, $page);
    }

    /**
     * Returns all usable items - paginated
     *
     * @param  int  $perPage  The number of items to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $searchText  The optional search text to filter items by name.
     * @param  array  $filter  The optional usable-item filters.
     * @return array The paginated usable item payload.
     */
    public function fetchCharacterUsableItems(int $perPage = 10, int $page = 1, string $searchText = '', array $filter = []): array
    {
        $slots = $this->getUsableItemsCollection($searchText, $filter);

        return $this->pagination->buildPaginatedDate($slots, $this->usableItemTransformer, $perPage, $page);
    }

    /**
     * Fetch inventory slot items.
     *
     * - Does not include alchemy or quest items.
     * - Items can also not be equipped.
     *
     * @return array The matching inventory slot ids.
     */
    public function findCharacterInventorySlotIds(): array
    {
        return $this->character
            ->inventory
            ->slots
            ->whereNotIn('item.type', ['quest', 'alchemy', 'gem', 'artifact'])
            ->where('equipped', false)
            ->sortBy('id')
            ->pluck('id')
            ->toArray();
    }

    /**
     * Fetch equipped slots (InventorySlot or SetSlot) with each slot’s item enriched for the transformer.
     *
     * @return array The transformed equipped slot payload.
     */
    public function fetchEquipped(): array
    {
        $inventory = Inventory::where('character_id', $this->character->id)->first();

        $slots = InventorySlot::query()
            ->where('inventory_id', $inventory->id)
            ->where('equipped', true)
            ->with('item')
            ->get()
            ->filter(fn ($slot) => ! is_null($slot->item))
            ->map(function ($slot) {
                $slot->setRelation('item', $this->itemEnricherFactory->buildItem($slot->item));

                return $slot;
            })
            ->values();

        if ($slots->isNotEmpty()) {
            $resource = new LeagueCollection($slots, $this->equippableItemTransformer);

            return $this->manager->createData($resource)->toArray();
        }

        $inventorySet = InventorySet::where('character_id', $this->character->id)
            ->where('is_equipped', true)
            ->first();

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
                $this->inventorySetEquippedName = 'Set '.($index + 1);
            }
        }

        $setSlots = SetSlot::query()
            ->where('inventory_set_id', $inventorySet->id)
            ->with('item')
            ->get()
            ->filter(fn ($slot) => ! is_null($slot->item))
            ->map(function ($slot) {
                $slot->setRelation('item', $this->itemEnricherFactory->buildItem($slot->item));

                return $slot;
            })
            ->values();

        $resource = new LeagueCollection($setSlots, $this->equippableItemTransformer);

        return $this->manager->createData($resource)->toArray();
    }

    /**
     * Resolve and store the inventory for the previously set positions.
     */
    public function setInventory(): CharacterInventoryService
    {
        $this->inventory = $this->getInventory();

        return $this;
    }

    /**
     * Resolve the inventory slots for the previously set positions, falling back to the equipped set's slots.
     *
     * @return Collection The resolved inventory slots.
     */
    private function getInventory(): Collection
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
     * Return the previously resolved inventory.
     *
     * @return Collection The resolved inventory slots.
     */
    public function inventory(): Collection
    {
        return $this->inventory;
    }

    /**
     * Fetches the type of the item.
     *
     * @param  Item  $item  The item to resolve the type for.
     * @return string The resolved item type.
     */
    public function getType(Item $item): string
    {
        return $this->fetchType($item->type);
    }

    /**
     * Delete an item from the inventory.
     *
     * @param  int  $itemId  The item id to delete.
     * @return array The delete result.
     */
    public function deleteItem(int $itemId): array
    {
        $slot = $this->character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false)
            ->where('item_id', $itemId)
            ->first();

        if (is_null($slot)) {
            return $this->errorResult('No matching item to destroy.');
        }

        $name = $slot->item->affix_name;

        $slot->delete();

        $this->character = $this->character->refresh();

        event(new UpdateCharacterInventoryCountEvent($this->character));

        return $this->successResult([
            'message' => 'Destroyed item: '.$name.'.',
        ]);
    }

    /**
     * Destroy all items in your inventory.
     *
     * - Will not destroy sets or items in sets.
     * - Will not destroy quest items or usable items.
     * - Will not destroy artifact items either.
     *
     * @return array The destroy-all result.
     */
    public function destroyAllItemsInInventory(): array
    {
        $slotIds = $this->findCharacterInventorySlotIds();

        $this->character->inventory->slots()->whereIn('id', $slotIds)->delete();

        $character = $this->character->refresh();

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Destroyed all items.',
            'inventory' => $this->getInventoryForType('inventory'),
        ]);
    }

    /**
     * Disenchant every disenchantable item in the character's normal inventory.
     *
     * @return array The disenchant-all result.
     */
    public function disenchantAllItemsInInventory(): array
    {
        $slots = $this->character->inventory->slots
            ->where('equipped', false)
            ->filter(function ($slot) {
                if (in_array($slot->item->type, ['quest', 'alchemy', 'artifact'], true)) {
                    return false;
                }

                return ! is_null($slot->item->item_prefix_id) || ! is_null($slot->item->item_suffix_id);
            })
            ->values();

        if ($slots->isEmpty()) {
            return $this->successResult([
                'message' => 'You have nothing to disenchant.',
            ]);
        }

        return $this->disenchantAllItems($slots, $this->character);
    }

    /**
     * Unequip an item from the player.
     *
     * @param  int  $inventorySlotId  The inventory slot id to unequip.
     * @return array The unequip result.
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

        event(new UpdateCharacterBaseDetailsEvent($character->refresh()));

        return $this->successResult([
            'message' => 'Unequipped item: '.$foundItem->item->affix_name,
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
     * @return array The unequip-all result.
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
     *
     * @param  int  $slotId  The alchemy bag slot id to destroy.
     * @return array The destroy result.
     */
    public function destroyAlchemyItem(int $slotId): array
    {
        $alchemyBag = $this->character->alchemyBag;
        $slot = is_null($alchemyBag)
            ? null
            : AlchemyBagSlot::where('id', $slotId)
                ->where('character_id', $this->character->id)
                ->where('alchemy_bag_id', $alchemyBag->id)
                ->with('item')
                ->first();

        if (is_null($slot)) {
            return $this->errorResult('No alchemy item found to destroy.');
        }

        $name = $slot->item->affix_name;

        $slot->delete();

        $character = $this->character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Destroyed Alchemy Item: '.$name.'.',
            'inventory' => [
                'usable_items' => $this->getInventoryForType('usable_items'),
            ],
        ]);
    }

    /**
     * Destroy all alchemy items.
     *
     * @return array The destroy-all result.
     */
    public function destroyAllAlchemyItems(): array
    {
        $alchemyBag = $this->character->alchemyBag;

        if (! is_null($alchemyBag)) {
            AlchemyBagSlot::where('alchemy_bag_id', $alchemyBag->id)
                ->where('character_id', $this->character->id)
                ->delete();
        }

        $character = $this->character->refresh();

        event(new UpdateTopBarEvent($character));
        event(new UpdateCharacterInventoryCountEvent($character));

        return $this->successResult([
            'message' => 'Destroyed All Alchemy Items.',
            'inventory' => [
                'usable_items' => $this->getInventoryForType('usable_items'),
            ],
        ]);
    }

    /**
     * Sell one inventory item for the character.
     *
     * @param  int  $itemId  The item id to sell.
     * @return array The sell result.
     */
    public function sellItem(int $itemId): array
    {

        $slot = $this->character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false)
            ->where('item_id', $itemId)
            ->first();

        if (is_null($slot)) {
            return $this->errorResult('No item found to be sell.');
        }

        $itemName = $slot->item->affix_name;

        $totalSoldFor = SellItemCalculator::fetchSalePriceWithAffixes($slot->item);

        event(new SellItemEvent($slot, $this->character));

        return $this->successResult([
            'message' => 'Sold '.$itemName.' for a total of '.number_format($totalSoldFor, 0).' gold.',
        ]);
    }

    /**
     * Disenchant one inventory item for the character.
     *
     * @param  int  $itemId  The item id to disenchant.
     * @return array The disenchant result.
     */
    public function disenchantItem(int $itemId): array
    {

        $slot = $this->character->inventory->slots()
            ->whereHas('item', static function ($query) {
                $query->whereNotIn('type', ['alchemy', 'quest', 'artifact', 'trinket']);
            })
            ->where('equipped', false)
            ->where('item_id', $itemId)
            ->first();

        if (is_null($slot)) {
            return $this->errorResult('No item found to disenchant.');
        }

        return $this->disenchantService->setUp($this->character)->disenchantItem($slot);
    }

    /**
     * Updates the character stats.
     *
     * @param  Character  $character  The character to update attack data for.
     */
    private function updateCharacterAttackDataCache(Character $character): void
    {
        CharacterAttackTypesCacheBuilder::dispatch($character->refresh());
    }

    /**
     * Fetch type based on accepted types.
     *
     * @param  string  $type  The raw item type to normalize.
     * @return string The normalized, accepted item type.
     */
    private function fetchType(string $type): string
    {
        if (in_array($type, ArmourType::allTypes())) {
            $type = 'armour';
        }

        $acceptedTypes = [
            ...ItemType::validWeapons(),
            'ring',
            'shield',
            'artifact',
            'spell',
            'trinket',
            'armour',
            'alchemy',
            'quest',
        ];

        if ($type === 'spell-damage' || $type === 'spell-healing') {
            $type = 'spell';
        }

        if (! in_array($type, $acceptedTypes)) {
            throw new Exception('Unknown Item type: '.$type);
        }

        return $type;
    }
}
