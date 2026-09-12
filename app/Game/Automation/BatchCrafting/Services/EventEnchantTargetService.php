<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\CraftEventTargetType;
use App\Game\Automation\BatchCrafting\Values\ResolvedEventEnchantTarget;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Values\GlobalEventSteps;

class EventEnchantTargetService
{
    public function __construct(
        private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
        private readonly CraftEventTargetService $craftEventTargetService,
    ) {}

    /**
     * Resolve the character's currently eligible Enchant Event goal, when one exists.
     *
     * @param Character $character The character running the batch.
     * @return GlobalEventGoal|null The currently eligible Enchant Event goal, or null when none is eligible.
     */
    public function currentGoal(Character $character): ?GlobalEventGoal
    {
        return $this->globalEventGoalEligibilityService->currentEnchantingGoalFor($character);
    }

    /**
     * Resolve the next real Event Crafting Inventory item eligible for enchanting.
     *
     * @param Character $character The character running the batch.
     * @param GlobalEventGoal $goal The current Enchant Event goal.
     * @return ResolvedEventEnchantTarget|null The resolved slot and item, or null when the inventory is empty.
     */
    public function resolveNextInventoryTarget(Character $character, GlobalEventGoal $goal): ?ResolvedEventEnchantTarget
    {
        $inventory = GlobalEventCraftingInventory::where('global_event_goal_id', $goal->id)
            ->where('character_id', $character->id)
            ->first();

        if (is_null($inventory)) {
            return null;
        }

        $slot = $inventory->craftingSlots()
            ->whereHas('item', function ($itemQuery) {
                $itemQuery->whereNotIn('type', ['quest', 'alchemy', 'gem', 'trinket', 'artifact']);
            })
            ->first();

        if (is_null($slot)) {
            return null;
        }

        return new ResolvedEventEnchantTarget($slot, $slot->item);
    }

    /**
     * Return the authoritative fallback Craft For Event cycle size.
     *
     * @return int The number of targets in one full fallback craft cycle.
     */
    public function fallbackCycleSize(): int
    {
        return $this->craftEventTargetService->cycleSize();
    }

    /**
     * Resolve a real currently craftable fallback item for the given fallback cycle position.
     *
     * @param Character $character The character running the batch.
     * @param int $cyclePosition The persisted fallback cycle position.
     * @return array{target: CraftEventTargetType, item: Item}|null The resolved fallback target and item, or null when unavailable.
     */
    public function resolveFallbackCraftTarget(Character $character, int $cyclePosition): ?array
    {
        $target = $this->craftEventTargetService->resolveTarget($cyclePosition);
        $item = $this->craftEventTargetService->resolveTargetItem($character, $target);

        if (is_null($item)) {
            return null;
        }

        return ['target' => $target, 'item' => $item];
    }

    /**
     * Add a successfully crafted fallback item into the character's Event Crafting Inventory for the goal.
     *
     * Never counts as an Event Enchant contribution on its own; only a completed enchant contributes.
     *
     * @param Character $character The character running the batch.
     * @param GlobalEventGoal $goal The current Enchant Event goal.
     * @param Item $item The successfully crafted fallback item.
     * @return void This method does not return a value.
     */
    public function addFallbackItemToInventory(Character $character, GlobalEventGoal $goal, Item $item): void
    {
        $inventory = GlobalEventCraftingInventory::firstOrCreate([
            'global_event_goal_id' => $goal->id,
            'character_id' => $character->id,
        ]);

        GlobalEventCraftingInventorySlot::create([
            'global_event_crafting_inventory_id' => $inventory->id,
            'item_id' => $item->id,
        ]);
    }

    /**
     * Resolve the specific factual reason Enchant For Event is not currently available.
     *
     * @param Character $character The character running the batch.
     * @return BatchCraftingEndReason The factual terminal Event reason.
     */
    public function resolveUnavailableReason(Character $character): BatchCraftingEndReason
    {
        $event = $this->globalEventGoalEligibilityService->eventForCharacterMap($character);

        if (is_null($event) || ! $this->globalEventGoalEligibilityService->isEventRunning($event)) {
            return BatchCraftingEndReason::EVENT_NOT_RUNNING;
        }

        if (! $this->globalEventGoalEligibilityService->isOnEventMap($character, $event)) {
            return BatchCraftingEndReason::EVENT_WRONG_MAP;
        }

        if ($event->current_event_goal_step !== GlobalEventSteps::ENCHANT) {
            return BatchCraftingEndReason::EVENT_STEP_CHANGED;
        }

        $goal = $this->globalEventGoalEligibilityService->latestGoalFor($event);

        if (! is_null($goal) && ! is_null($goal->max_enchants) && $goal->total_enchants >= $goal->max_enchants) {
            return BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
        }

        return BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS;
    }
}
