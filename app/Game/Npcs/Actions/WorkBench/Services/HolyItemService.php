<?php

namespace App\Game\Npcs\Actions\WorkBench\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use App\Flare\Pagination\Pagination;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Items\Services\HolyItemBonusGenerator;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Core\Items\Values\HolyItemLevel;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Npcs\Actions\WorkBench\Transformers\WorkBenchHolyOilTransformer;
use App\Game\Npcs\Actions\WorkBench\Transformers\WorkBenchTargetTransformer;
use App\Game\Npcs\Actions\WorkBench\Values\HolyOilBatchApplicationResult;
use Illuminate\Database\Eloquent\Collection as DBCollection;
use Illuminate\Support\Collection;

class HolyItemService
{
    use ResponseBuilder;

    /**
     * @param  HolyItemBonusGenerator  $holyItemBonusGenerator
     * @param  Pagination  $pagination
     * @param  WorkBenchTargetTransformer  $workBenchTargetTransformer
     * @param  WorkBenchHolyOilTransformer  $workBenchHolyOilTransformer
     * @param  CraftingItemPreviewTransformer  $craftingItemPreviewTransformer
     */
    public function __construct(
        private readonly HolyItemBonusGenerator $holyItemBonusGenerator,
        private readonly Pagination $pagination,
        private readonly WorkBenchTargetTransformer $workBenchTargetTransformer,
        private readonly WorkBenchHolyOilTransformer $workBenchHolyOilTransformer,
        private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer,
    ) {}

    /**
     * Fetches a paginated, searchable list of inventory items eligible for Holy Oil application.
     *
     * @param  Character  $character  The character requesting target items.
     * @param  int  $perPage  The number of items to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $search  The optional search text to filter items by name.
     * @return array The paginated target item payload.
     */
    public function fetchPaginatedTargetItems(Character $character, int $perPage, int $page, string $search = ''): array
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        $query = InventorySlot::with(['item.itemPrefix', 'item.itemSuffix', 'item.appliedHolyStacks', 'item.itemSkillProgressions'])
            ->where('inventory_id', $inventory->id)
            ->where('equipped', false)
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->whereRaw('holy_stacks > (select count(*) from holy_stacks where holy_stacks.item_id = items.id)');
            });

        if ($search !== '') {
            $query->whereHas('item', function ($itemQuery) use ($search) {
                $itemQuery->where('name', 'LIKE', '%'.$search.'%');
            });
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->workBenchTargetTransformer);
    }

    /**
     * Fetches a paginated, searchable list of Holy Oils from the character's Alchemy Bag.
     *
     * @param  Character  $character  The character requesting Holy Oils.
     * @param  int  $perPage  The number of items to return per page.
     * @param  int  $page  The page number to return.
     * @param  string  $search  The optional search text to filter items by name.
     * @return array The paginated Holy Oil payload.
     */
    public function fetchPaginatedHolyOils(Character $character, int $perPage, int $page, string $search = ''): array
    {
        if (is_null($character->alchemyBag)) {
            return $this->pagination->paginateCollectionResponse(new Collection, $perPage, $page);
        }

        $query = $character->alchemyBag->slots()
            ->with('item')
            ->where('character_id', $character->id)
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->where('can_use_on_other_items', true)
                    ->whereNotNull('holy_level');
            });

        if ($search !== '') {
            $query->whereHas('item', function ($itemQuery) use ($search) {
                $itemQuery->where('name', 'LIKE', '%'.$search.'%');
            });
        }

        $paginator = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return $this->pagination->transformLengthAwarePaginator($paginator, $this->workBenchHolyOilTransformer);
    }

    /**
     * Fetch the character's Holy Oil target items and available Holy Oils, with cost facts.
     *
     * @param  Character  $character  The character requesting smithing items.
     * @return array The target items, available Holy Oils, and their cost lookup.
     */
    public function fetchSmithingItems(Character $character): array
    {
        $slots = $this->getSlots($character);

        $items = $this->fetchValidItems($slots)->reverse()->values();
        $alchemyItems = $this->fetchAlchemyItems($character)->values();
        $costs = $this->buildCostLookup($items, $alchemyItems);

        return $this->successResult([
            'items' => $items->map(fn ($slot) => $this->workBenchTargetTransformer->transform($slot))->values(),
            'alchemy_items' => $alchemyItems->map(fn ($slot) => $this->workBenchHolyOilTransformer->transform($slot))->values(),
            'costs' => $costs,
        ]);
    }

    /**
     * Build the Holy Oil cost lookup, keyed by target inventory slot id and alchemy bag slot id.
     *
     * @param  Collection  $items  The candidate target item slots.
     * @param  Collection  $alchemyItems  The candidate Holy Oil Alchemy Bag slots.
     * @return array The cost lookup, keyed by target slot id then Alchemy Bag slot id.
     */
    private function buildCostLookup(Collection $items, Collection $alchemyItems): array
    {
        $costs = [];

        foreach ($items as $itemSlot) {
            foreach ($alchemyItems as $alchemySlot) {
                $costs[$itemSlot->id][$alchemySlot->id] = $this->getCost($itemSlot->item, $alchemySlot->item);
            }
        }

        return $costs;
    }

    /**
     * Apply a Holy Oil from the request params to the requested target item.
     *
     * @param  Character  $character  The character applying the oil.
     * @param  array  $params  The request params, including the target slot and Alchemy slot ids.
     * @return array The application result payload.
     */
    public function applyOil(Character $character, array $params): array
    {
        $inventory = Inventory::where('character_id', $character->id)->first();
        $itemSlot = isset($params['inventory_slot_id'])
            ? InventorySlot::where('inventory_id', $inventory->id)->where('id', $params['inventory_slot_id'])->first()
            : InventorySlot::where('inventory_id', $inventory->id)->where('item_id', $params['item_id'])->first();
        $alchemySlotQuery = AlchemyBagSlot::where('alchemy_bag_id', $character->alchemyBag?->id)
            ->where('character_id', $character->id)
            ->with('item');

        if (isset($params['alchemy_slot_id'])) {
            $alchemySlotQuery->where('id', $params['alchemy_slot_id']);
        } else {
            $alchemySlotQuery->where('item_id', $params['alchemy_item_id'] ?? null);
        }

        $alchemySlot = $alchemySlotQuery->first();

        if (is_null($itemSlot)) {
            return $this->errorResult('No item found to apply the oil to.');
        }

        if ($itemSlot->item->type === 'trinket' || $itemSlot->item->type === 'artifact') {
            return $this->errorResult('Trinkets and Artifacts cannot have holy oils applied.');
        }

        if (is_null($alchemySlot) || ! $alchemySlot->item->can_use_on_other_items || is_null($alchemySlot->item->holy_level)) {
            return $this->errorResult(
                'No alchemy items to apply. Craft some.'
            );
        }

        $cost = $this->getCost($itemSlot->item, $alchemySlot->item);

        if ($cost > $character->gold_dust) {

            return $this->errorResult('Not enough gold dust to apply this oil.');
        }

        if (! $this->canApplyAdditionalStacks($itemSlot->item)) {
            return $this->errorResult('Error: No stacks left.');
        }

        event(new CraftedItemTimeOutEvent($character));

        $character->update([
            'gold_dust' => $character->gold_dust - $cost,
        ]);

        $slot = $this->applyStack($itemSlot, $alchemySlot);

        $character = $character->refresh();

        event(new ServerMessageEvent($character->user, 'Applied Holy Oil to: '.$slot->item->affix_name, $slot->id));

        event(new UpdateCharacterCurrenciesEvent($character));

        event(new UpdateCharacterInventoryCountEvent($character));

        if (($slot->item->holy_stacks - $slot->item->holy_stacks_applied) === 0) {
            event(new ServerMessageEvent($character->user, 'You have applied the max stacks allowed. Item has been removed from list of items you can use at the work bench.'));
        }

        return array_merge($this->fetchSmithingItems($character), [
            'result_preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
        ]);
    }

    /**
     * Calculate the Gold Dust cost to apply the given Holy Oil to the given target item.
     *
     * @param  Item  $item  The target item.
     * @param  Item  $alchemyItem  The Holy Oil item being applied.
     * @return int The Gold Dust cost.
     */
    public function getCost(Item $item, Item $alchemyItem): int
    {
        $baseCost = $item->holy_stacks * 100;
        $totalCost = $baseCost * $alchemyItem->holy_level;

        return $totalCost;
    }

    /**
     * Determine whether an item is a currently eligible Holy Oil target.
     *
     * @param  Item  $item  The item being checked.
     * @return bool True when the item can still receive an applied Holy stack.
     */
    public function isEligibleHolyOilTarget(Item $item): bool
    {
        return ! in_array($item->type, ['trinket', 'artifact'], true) && ($item->holy_stacks - $item->holy_stacks_applied) > 0;
    }

    /**
     * Determine whether the character owns at least one currently eligible loose Inventory Holy Oil target item.
     *
     * Uses the same eligibility semantics as actual Holy Oil application: the item must be
     * unequipped, must not be a Trinket or Artifact, and must have remaining Holy stack capacity.
     *
     * @param  Character  $character  The character being checked.
     * @return bool True when at least one eligible loose Inventory target item exists.
     */
    public function hasEligibleInventoryTarget(Character $character): bool
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        if (is_null($inventory)) {
            return false;
        }

        return InventorySlot::where('inventory_id', $inventory->id)
            ->where('equipped', false)
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->whereNotIn('type', ['trinket', 'artifact'])
                    ->whereRaw('holy_stacks > (select count(*) from holy_stacks where holy_stacks.item_id = items.id)');
            })
            ->exists();
    }

    /**
     * Determine whether the character owns at least one Inventory Set containing a currently eligible Holy Oil target item.
     *
     * Uses the same eligibility semantics as the Set target selector: the Set must be owned,
     * unequipped, and not the special Crafted Items Set, and it must contain at least one item
     * that is not a Trinket or Artifact with remaining Holy stack capacity.
     *
     * @param  Character  $character  The character being checked.
     * @return bool True when at least one eligible Set target item exists.
     */
    public function hasEligibleInventorySetTarget(Character $character): bool
    {
        return InventorySet::where('character_id', $character->id)
            ->where('is_equipped', false)
            ->where(fn ($query) => $query->whereNull('special_type')
                ->orWhere('special_type', '!=', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE))
            ->whereHas('slots', function ($slotQuery) {
                $slotQuery->whereHas('item', function ($itemQuery) {
                    $itemQuery->whereNotIn('type', ['trinket', 'artifact'])
                        ->whereRaw('holy_stacks > (select count(*) from holy_stacks where holy_stacks.item_id = items.id)');
                });
            })
            ->exists();
    }

    /**
     * Determine whether the character owns at least one usable Holy Oil.
     *
     * @param  Character  $character  The character being checked.
     * @return bool True when at least one usable Holy Oil exists in the Alchemy Bag.
     */
    public function hasEligibleHolyOil(Character $character): bool
    {
        if (is_null($character->alchemyBag)) {
            return false;
        }

        return $character->alchemyBag->slots()
            ->where('character_id', $character->id)
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->where('can_use_on_other_items', true)->whereNotNull('holy_level');
            })
            ->exists();
    }

    /**
     * Apply one Holy Oil stack to an Inventory target for Batch Crafting.
     *
     * @param  Character  $character  The character running the batch.
     * @param  int  $inventorySlotId  The target Inventory slot id.
     * @param  int  $alchemySlotId  The Holy Oil Alchemy Bag slot id.
     * @return HolyOilBatchApplicationResult The factual outcome of the application attempt.
     */
    public function applyOilForBatch(Character $character, int $inventorySlotId, int $alchemySlotId): HolyOilBatchApplicationResult
    {
        $inventory = Inventory::where('character_id', $character->id)->first();
        $itemSlot = is_null($inventory) ? null : InventorySlot::where('inventory_id', $inventory->id)->where('id', $inventorySlotId)->first();

        if (is_null($itemSlot) || in_array($itemSlot->item->type, ['trinket', 'artifact'], true)) {
            return HolyOilBatchApplicationResult::failed('invalid_target');
        }

        $alchemySlot = $this->resolveAlchemySlot($character, $alchemySlotId);

        if (is_null($alchemySlot)) {
            return HolyOilBatchApplicationResult::failed('invalid_oil');
        }

        if (! $this->canApplyAdditionalStacks($itemSlot->item)) {
            return HolyOilBatchApplicationResult::failed('saturated');
        }

        $cost = $this->getCost($itemSlot->item, $alchemySlot->item);

        if ($cost > $character->gold_dust) {
            return HolyOilBatchApplicationResult::failed('not_enough_gold_dust');
        }

        $character->update(['gold_dust' => $character->gold_dust - $cost]);

        $slot = $this->applyStack($itemSlot, $alchemySlot);
        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));
        event(new UpdateCharacterInventoryCountEvent($character));

        return HolyOilBatchApplicationResult::success($slot, $cost, $this->isSaturated($slot->item));
    }

    /**
     * Apply one Holy Oil stack to an Inventory Set target for Batch Crafting.
     *
     * @param  Character  $character  The character running the batch.
     * @param  InventorySet  $set  The Inventory Set owning the target slot.
     * @param  int  $setSlotId  The target Set slot id.
     * @param  int  $alchemySlotId  The Holy Oil Alchemy Bag slot id.
     * @return HolyOilBatchApplicationResult The factual outcome of the application attempt.
     */
    public function applyOilToSetSlotForBatch(Character $character, InventorySet $set, int $setSlotId, int $alchemySlotId): HolyOilBatchApplicationResult
    {
        $setSlot = SetSlot::where('inventory_set_id', $set->id)->where('id', $setSlotId)->first();

        if (is_null($setSlot) || in_array($setSlot->item->type, ['trinket', 'artifact'], true)) {
            return HolyOilBatchApplicationResult::failed('invalid_target');
        }

        $alchemySlot = $this->resolveAlchemySlot($character, $alchemySlotId);

        if (is_null($alchemySlot)) {
            return HolyOilBatchApplicationResult::failed('invalid_oil');
        }

        if (! $this->canApplyAdditionalStacks($setSlot->item)) {
            return HolyOilBatchApplicationResult::failed('saturated');
        }

        $cost = $this->getCost($setSlot->item, $alchemySlot->item);

        if ($cost > $character->gold_dust) {
            return HolyOilBatchApplicationResult::failed('not_enough_gold_dust');
        }

        $character->update(['gold_dust' => $character->gold_dust - $cost]);

        $slot = $this->applyStackToSetSlot($setSlot, $alchemySlot);
        $character = $character->refresh();

        event(new UpdateCharacterCurrenciesEvent($character));
        event(new UpdateCharacterInventoryCountEvent($character));

        return HolyOilBatchApplicationResult::success($slot, $cost, $this->isSaturated($slot->item));
    }

    /**
     * Resolve an owned, usable Holy Oil Alchemy Bag slot by id.
     *
     * @param  Character  $character  The character being checked.
     * @param  int  $alchemySlotId  The requested Alchemy Bag slot id.
     * @return AlchemyBagSlot|null The resolved slot, or null when it is not a legal Holy Oil.
     */
    private function resolveAlchemySlot(Character $character, int $alchemySlotId): ?AlchemyBagSlot
    {
        $alchemySlot = AlchemyBagSlot::where('id', $alchemySlotId)
            ->where('alchemy_bag_id', $character->alchemyBag?->id)
            ->where('character_id', $character->id)
            ->with('item')
            ->first();

        if (is_null($alchemySlot) || ! $alchemySlot->item->can_use_on_other_items || is_null($alchemySlot->item->holy_level)) {
            return null;
        }

        return $alchemySlot;
    }

    /**
     * Determine whether the item has reached its maximum applied Holy stacks.
     *
     * @param  Item  $item  The item being checked.
     * @return bool True when no further Holy stacks can be applied.
     */
    private function isSaturated(Item $item): bool
    {
        return ($item->holy_stacks - $item->holy_stacks_applied) <= 0;
    }

    /**
     * Determine whether the item has any remaining Holy stack capacity.
     *
     * @param  Item  $item  The target item.
     * @return bool True when at least one more Holy stack can be applied.
     */
    private function canApplyAdditionalStacks(Item $item): bool
    {
        $stacksLeft = $item->holy_stacks - $item->holy_stacks_applied;

        return $stacksLeft > 0;
    }

    /**
     * Apply one Holy stack from the Alchemy Bag slot's oil to the target loose Inventory item.
     *
     * @param  InventorySlot  $itemSlot  The target Inventory slot.
     * @param  AlchemyBagSlot  $alchemyItemSlot  The Holy Oil Alchemy Bag slot.
     * @return InventorySlot The resulting Inventory slot.
     */
    private function applyStack(InventorySlot $itemSlot, AlchemyBagSlot $alchemyItemSlot): InventorySlot
    {
        $holyItemLevel = HolyItemLevel::from($alchemyItemSlot->item->holy_level);

        if ($itemSlot->item->appliedHolyStacks->isEmpty()) {
            $newItem = $this->duplicateItemForNewHolyStack($itemSlot->item, $holyItemLevel);
            $inventory = Inventory::find($itemSlot->inventory_id);

            $itemSlot->delete();

            $this->decrementAlchemySlot($alchemyItemSlot);

            return $inventory->slots()->create([
                'inventory_id' => $inventory->id,
                'item_id' => $newItem->id,
            ]);
        }

        $this->decrementAlchemySlot($alchemyItemSlot);
        $this->createAppliedHolyStack($itemSlot->item, $holyItemLevel);

        return $itemSlot->refresh();
    }

    /**
     * Apply one Holy Oil stack to a Set slot's item, in place.
     *
     * @param  SetSlot  $setSlot  The target Set slot.
     * @param  AlchemyBagSlot  $alchemyItemSlot  The Holy Oil Alchemy Bag slot being consumed.
     * @return SetSlot The updated Set slot.
     */
    private function applyStackToSetSlot(SetSlot $setSlot, AlchemyBagSlot $alchemyItemSlot): SetSlot
    {
        $holyItemLevel = HolyItemLevel::from($alchemyItemSlot->item->holy_level);

        if ($setSlot->item->appliedHolyStacks->isEmpty()) {
            $newItem = $this->duplicateItemForNewHolyStack($setSlot->item, $holyItemLevel);

            $this->decrementAlchemySlot($alchemyItemSlot);

            $setSlot->update(['item_id' => $newItem->id]);

            return $setSlot->refresh();
        }

        $this->decrementAlchemySlot($alchemyItemSlot);
        $this->createAppliedHolyStack($setSlot->item, $holyItemLevel);

        return $setSlot->refresh();
    }

    /**
     * Duplicate an item as the sellable, market-flagged base for its first applied Holy stack.
     *
     * @param  Item  $item  The item being duplicated.
     * @param  HolyItemLevel  $holyItemLevel  The applied Holy Oil's level.
     * @return Item The newly duplicated item, already carrying its first applied Holy stack.
     */
    private function duplicateItemForNewHolyStack(Item $item, HolyItemLevel $holyItemLevel): Item
    {
        $newItem = $item->duplicate();

        $newItem->update([
            'market_sellable' => true,
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
        ]);

        $this->createAppliedHolyStack($newItem, $holyItemLevel);

        return $newItem;
    }

    /**
     * Create one applied Holy stack record for the item.
     *
     * @param  Item  $item  The item receiving the applied Holy stack.
     * @param  HolyItemLevel  $holyItemLevel  The applied Holy Oil's level.
     * @return void This method does not return a value.
     */
    private function createAppliedHolyStack(Item $item, HolyItemLevel $holyItemLevel): void
    {
        $item->appliedHolyStacks()->create([
            'item_id' => $item->id,
            'devouring_darkness_bonus' => $this->holyItemBonusGenerator->getRandomDevoidanceIncrease($holyItemLevel),
            'stat_increase_bonus' => $this->holyItemBonusGenerator->getRandomStatIncrease($holyItemLevel) / 100,
        ]);
    }

    /**
     * Fetch the character's Alchemy Bag slots holding usable Holy Oils.
     *
     * @param  Character  $character  The character requesting Holy Oils.
     * @return Collection The Holy Oil Alchemy Bag slots.
     */
    private function fetchAlchemyItems(Character $character): Collection
    {
        if (is_null($character->alchemyBag)) {
            return collect();
        }

        return $character->alchemyBag->slots()
            ->where('character_id', $character->id)
            ->whereHas('item', function ($query) {
                $query->where('can_use_on_other_items', true)
                    ->whereNotNull('holy_level');
            })
            ->with('item')
            ->get();
    }

    /**
     * Filter the given slots down to those whose item still has remaining Holy stack capacity.
     *
     * @param  DBCollection  $slots  The candidate slots.
     * @return Collection The slots with remaining Holy stack capacity.
     */
    private function fetchValidItems(DBCollection $slots): Collection
    {
        return $slots->filter(function ($slot) {
            return ($slot->item->holy_stacks - $slot->item->holy_stacks_applied) > 0;
        });
    }

    /**
     * Fetch the character's unequipped loose Inventory slots.
     *
     * @param  Character  $character  The character requesting slots.
     * @return DBCollection The unequipped Inventory slots.
     */
    private function getSlots(Character $character): DBCollection
    {
        $inventory = Inventory::where('character_id', $character->id)->first();

        return InventorySlot::where('inventory_slots.inventory_id', $inventory->id)->where('inventory_slots.equipped', false)->get();
    }

    /**
     * Consume one unit of the given Alchemy Bag slot's Holy Oil, deleting the slot when exhausted.
     *
     * @param  AlchemyBagSlot  $alchemySlot  The Holy Oil Alchemy Bag slot.
     * @return void This method does not return a value.
     */
    private function decrementAlchemySlot(AlchemyBagSlot $alchemySlot): void
    {
        if ($alchemySlot->amount <= 1) {
            $alchemySlot->delete();

            return;
        }

        $alchemySlot->update(['amount' => $alchemySlot->amount - 1]);
    }
}
