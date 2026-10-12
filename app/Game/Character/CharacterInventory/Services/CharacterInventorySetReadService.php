<?php

namespace App\Game\Character\CharacterInventory\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use App\Flare\Models\SetSlot;
use App\Flare\Pagination\Pagination;
use App\Game\Character\CharacterInventory\Transformers\InventorySetOptionTransformer;
use App\Game\Character\CharacterInventory\Transformers\InventoryTransformer;
use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as LeagueCollection;

class CharacterInventorySetReadService
{
    /**
     * @param ItemEnricherFactory $itemEnricherFactory
     * @param InventoryTransformer $inventoryTransformer
     * @param InventorySetService $inventorySetService
     * @param Pagination $pagination
     * @param Manager $manager
     * @param InventorySetOptionTransformer $inventorySetOptionTransformer
     * @param EquippableItemTransformer $equippableItemTransformer
     */
    public function __construct(
        private readonly ItemEnricherFactory $itemEnricherFactory,
        private readonly InventoryTransformer $inventoryTransformer,
        private readonly InventorySetService $inventorySetService,
        private readonly Pagination $pagination,
        private readonly Manager $manager,
        private readonly InventorySetOptionTransformer $inventorySetOptionTransformer,
        private readonly EquippableItemTransformer $equippableItemTransformer,
    ) {}

    /**
     * Return paginated Inventory Set summaries.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function inventorySets(Character $character, int $perPage = 10, int $page = 1): array
    {
        $sets = $character->inventorySets()
            ->with('slots.item')
            ->orderByRaw('case when special_type = ? then 1 else 0 end', [InventorySet::BATCH_CRAFTING_SPECIAL_TYPE])
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function (InventorySet $inventorySet, int $index) use ($character): array {
                $inventorySet->slots->each(function (SetSlot $slot): void {
                    $slot->setRelation('item', $this->itemEnricherFactory->buildItem($slot->item));
                });

                $slotCount = $inventorySet->currentSlotCount();
                $remainingSpace = max(0, $character->inventory_max - $character->getInventoryCount());

                return [
                    'name' => $inventorySet->name ?? 'Set '.($index + 1),
                    'items' => array_reverse($this->manager->createData(new LeagueCollection($inventorySet->slots, $this->inventoryTransformer))->toArray()),
                    'equippable' => $inventorySet->can_be_equipped,
                    'set_id' => $inventorySet->id,
                    'equipped' => $inventorySet->is_equipped,
                    'is_equippable' => $this->inventorySetService->isSetEquippable($inventorySet),
                    'is_batch_crafting_set' => $inventorySet->isBatchCraftingSet(),
                    'max_slots' => $inventorySet->max_slots,
                    'current_slots' => $slotCount,
                    'remaining_slots' => $inventorySet->remainingSlots(),
                    'can_empty' => $slotCount <= $remainingSpace,
                    'empty_disabled_reason' => $slotCount > $remainingSpace ? 'Your inventory does not have enough room to empty this set.' : null,
                ];
            });

        return $this->pagination->paginateCollectionResponse($sets, $perPage, $page);
    }

    /**
     * Return paginated empty Batch Crafting destination options.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @return array
     */
    public function paginatedDestinationOptions(Character $character, int $perPage = 10, int $page = 1, string $search = ''): array
    {
        $query = $this->optionQuery($character)
            ->whereDoesntHave('slots');

        if ($search !== '') {
            $query->where('name', 'LIKE', '%'.$search.'%');
        }

        return $this->pagination->transformLengthAwarePaginator(
            $query->paginate($perPage, ['*'], 'page', $page),
            $this->inventorySetOptionTransformer,
        );
    }

    /**
     * Return paginated Inventory Sets containing Holy Oil targets.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @return array
     */
    public function paginatedHolyOilTargetOptions(Character $character, int $perPage = 10, int $page = 1, string $search = ''): array
    {
        $query = $this->optionQuery($character)
            ->whereHas('slots', function ($slotQuery): void {
                $slotQuery->whereHas('item', function ($itemQuery): void {
                    $itemQuery->whereNotIn('type', ['trinket', 'artifact'])
                        ->whereRaw('holy_stacks > (select count(*) from holy_stacks where holy_stacks.item_id = items.id)');
                });
            });

        if ($search !== '') {
            $query->where('name', 'LIKE', '%'.$search.'%');
        }

        return $this->pagination->transformLengthAwarePaginator(
            $query->paginate($perPage, ['*'], 'page', $page),
            $this->inventorySetOptionTransformer,
        );
    }

    /**
     * Resolve a valid normal Inventory Set target.
     *
     * @param Character $character
     * @param int $setId
     * @return InventorySet|null
     */
    public function resolveValidTarget(Character $character, int $setId): ?InventorySet
    {
        $set = $character->inventorySets()->find($setId);

        return is_null($set) || $set->is_equipped || $set->isBatchCraftingSet() ? null : $set;
    }

    /**
     * Resolve an empty normal Batch Crafting destination set.
     *
     * @param Character $character
     * @param int $setId
     * @return InventorySet|null
     */
    public function resolveEmptyBatchCraftingDestination(Character $character, int $setId): ?InventorySet
    {
        return $character->inventorySets()
            ->where('id', $setId)
            ->where('is_equipped', false)
            ->where(fn ($query) => $query->whereNull('special_type')->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE))
            ->whereDoesntHave('slots')
            ->first();
    }

    /**
     * Resolve any owned Inventory Set.
     *
     * @param Character $character
     * @param int $setId
     * @return InventorySet|null
     */
    public function findOwned(Character $character, int $setId): ?InventorySet
    {
        return $character->inventorySets()->find($setId);
    }

    /**
     * Return paginated Items from an owned Inventory Set.
     *
     * @param Character $character
     * @param int $perPage
     * @param int $page
     * @param string $search
     * @param array $filters
     * @return array
     */
    public function setItems(Character $character, int $perPage = 10, int $page = 1, string $search = '', array $filters = []): array
    {
        $sets = $character->inventorySets();
        $set = isset($filters['set_id'])
            ? $sets->where('id', $filters['set_id'])->first()
            : $sets->where('is_equipped', true)->first();
        $slots = $set->slots;

        if ($search !== '') {
            $slots = $slots->filter(function ($slot) use ($search): bool {
                return Str::contains(Str::lower($slot->item->name), Str::lower($search))
                    || ($slot->item->itemPrefix && Str::contains(Str::lower($slot->item->itemPrefix->name), Str::lower($search)))
                    || ($slot->item->itemSuffix && Str::contains(Str::lower($slot->item->itemSuffix->name), Str::lower($search)));
            });
        }

        $slots = $slots->map(function ($slot) {
            $slot->item = $this->itemEnricherFactory->buildItem($slot->item);

            return $slot;
        })->sortByDesc(fn ($slot) => $slot->item->total_damage_stat_bonus)->values();

        return $this->pagination->buildPaginatedDate($slots, $this->equippableItemTransformer, $perPage, $page);
    }

    /**
     * Return the equipped Inventory Set display name.
     *
     * @param Character $character
     * @return string|null
     */
    public function equippedSetName(Character $character): ?string
    {
        $equippedSet = $character->inventorySets()->where('is_equipped', true)->first();

        if (is_null($equippedSet)) {
            return null;
        }

        $index = $character->inventorySets->search(fn ($set): bool => $set->id === $equippedSet->id);

        return $equippedSet->name ?? 'Set '.($index + 1);
    }

    /**
     * Return unequipped normal Inventory Set options.
     *
     * @param Character $character
     * @return array
     */
    public function usableSets(Character $character): array
    {
        $normalSets = fn ($query) => $query->whereNull('special_type')->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE);
        $selectableSets = InventorySet::where('is_equipped', false)->where('character_id', $character->id)->where($normalSets)->get();
        $setIds = InventorySet::where('character_id', $character->id)->where($normalSets)->pluck('id')->all();

        return $selectableSets->map(function (InventorySet $set) use ($setIds): array {
            $index = array_search($set->id, $setIds, true) + 1;

            return ['index' => $index, 'id' => $set->id, 'name' => $set->name ?? 'Set '.$index, 'equipped' => false];
        })->all();
    }

    /**
     * Build the common selectable Inventory Set query.
     *
     * @param Character $character
     * @return HasMany
     */
    private function optionQuery(Character $character): HasMany
    {
        return $character->inventorySets()
            ->select('inventory_sets.*')
            ->selectSub(
                fn (QueryBuilder $query) => $query->selectRaw('count(*) + 1')
                    ->from('inventory_sets as numbered_inventory_sets')
                    ->whereColumn('numbered_inventory_sets.character_id', 'inventory_sets.character_id')
                    ->whereColumn('numbered_inventory_sets.id', '<', 'inventory_sets.id')
                    ->where(fn ($query) => $query->whereNull('numbered_inventory_sets.special_type')->orWhere('numbered_inventory_sets.special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)),
                'set_number',
            )
            ->withCount('slots')
            ->where('is_equipped', false)
            ->where(fn ($query) => $query->whereNull('special_type')->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE))
            ->orderBy('id');
    }
}
