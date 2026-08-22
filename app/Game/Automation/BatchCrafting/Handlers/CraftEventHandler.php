<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftEventTargetType;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Services\CraftEventTargetService;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Events\Services\GlobalEventGoalEligibilityService;
use App\Game\Events\Values\GlobalEventSteps;
use App\Game\Skills\Handlers\HandleUpdatingCraftingGlobalEventGoal;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\CraftingMessageMode;

#[HandlesBatchCraftingMode(BatchCraftingType::CRAFT, CraftingBatchMode::EVENT)]
class CraftEventHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
        private readonly CraftEventTargetService $craftEventTargetService,
        private readonly GlobalEventGoalEligibilityService $globalEventGoalEligibilityService,
        private readonly HandleUpdatingCraftingGlobalEventGoal $handleUpdatingCraftingGlobalEventGoal,
    ) {}

    /**
     * Execute one Craft For Event Batch Crafting action slot for the running batch.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the action slot.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $goal = $this->globalEventGoalEligibilityService->currentCraftingGoalFor($character);

        if (is_null($goal)) {
            return BatchCraftingOperationResult::ended($this->resolveUnavailableReason($character));
        }

        $progress = $batchCrafting->progress;
        $target = $this->craftEventTargetService->resolveTarget($progress['event_cycle_position']);
        $progress['event_cycle_position'] = ($progress['event_cycle_position'] + 1) % $this->craftEventTargetService->cycleSize();
        $batchCrafting->update(['progress' => $progress]);

        $item = $this->craftEventTargetService->resolveTargetItem($character, $target);

        if (is_null($item)) {
            $progress['current_item_id'] = null;
            $progress['current_item_name'] = null;
            $progress['current_crafting_type'] = $target->skillGroup()->value;
            $batchCrafting->update(['progress' => $progress]);

            return BatchCraftingOperationResult::skipped();
        }

        $progress['current_item_id'] = $item->id;
        $progress['current_item_name'] = $item->affix_name ?? $item->name;
        $progress['current_crafting_type'] = $target->skillGroup();
        $batchCrafting->update(['progress' => $progress]);

        return $this->craftForEvent($batchCrafting, $character, $item, $target, $goal);
    }

    /**
     * Craft the resolved item and contribute it to the current Craft Event goal.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @param  Item  $item  The resolved item to craft.
     * @param  CraftEventTargetType  $target  The resolved cycle target.
     * @param  GlobalEventGoal  $goal  The current Craft Event goal, before this attempt.
     * @return BatchCraftingOperationResult The outcome of the craft attempt.
     */
    private function craftForEvent(BatchCrafting $batchCrafting, Character $character, Item $item, CraftEventTargetType $target, GlobalEventGoal $goal): BatchCraftingOperationResult
    {
        $goldCost = $this->craftingBatchAttemptService->goldCostFor($character, $item);

        $craftingType = $target === CraftEventTargetType::WEAPON ? $item->type : $target->craftingType();
        $craftResult = $this->craftingService->craftForBatch($character, $item, $craftingType, CraftingMessageMode::BATCH_CRAFTING);

        if (! $craftResult['success']) {
            return $this->craftingBatchAttemptService->translateFailure($craftResult['reason'], $goldCost);
        }

        $this->handleUpdatingCraftingGlobalEventGoal->handleUpdatingCraftingGlobalEventGoal($character, $craftResult['item']);

        $this->persistXpGained($batchCrafting, $craftResult['xp_gained']);

        $result = BatchCraftingOperationResult::crafted($goldCost);
        $endReason = $this->resolveEndReasonAfterContribution($character, $goal);

        if (! is_null($endReason)) {
            return $result->withEndReason($endReason);
        }

        return $result;
    }

    /**
     * Determine whether the Event goal completed or moved away from Crafting as a result of this contribution.
     *
     * @param  Character  $character  The character running the batch.
     * @param  GlobalEventGoal  $goal  The Craft Event goal contributed to.
     * @return BatchCraftingEndReason|null The terminal end reason, or null when the batch should continue.
     */
    private function resolveEndReasonAfterContribution(Character $character, GlobalEventGoal $goal): ?BatchCraftingEndReason
    {
        if (is_null($this->globalEventGoalEligibilityService->currentCraftingGoalFor($character))) {
            $goal = $goal->fresh();

            if (! is_null($goal) && $goal->total_crafts >= $goal->max_crafts) {
                return BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
            }

            return $this->resolveUnavailableReason($character);
        }

        return null;
    }

    /**
     * Resolve the specific factual reason Craft For Event is not currently available.
     *
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingEndReason The factual terminal Event reason.
     */
    private function resolveUnavailableReason(Character $character): BatchCraftingEndReason
    {
        $event = $this->globalEventGoalEligibilityService->eventForCharacterMap($character);

        if (is_null($event) || ! $this->globalEventGoalEligibilityService->isEventRunning($event)) {
            return BatchCraftingEndReason::EVENT_NOT_RUNNING;
        }

        if (! $this->globalEventGoalEligibilityService->isOnEventMap($character, $event)) {
            return BatchCraftingEndReason::EVENT_WRONG_MAP;
        }

        if ($event->current_event_goal_step !== GlobalEventSteps::CRAFT) {
            return BatchCraftingEndReason::EVENT_STEP_CHANGED;
        }

        $goal = $this->globalEventGoalEligibilityService->latestGoalFor($event);

        if (! is_null($goal) && ! is_null($goal->max_crafts) && $goal->total_crafts >= $goal->max_crafts) {
            return BatchCraftingEndReason::EVENT_GOAL_COMPLETE;
        }

        return BatchCraftingEndReason::EVENT_NO_CRAFTABLE_ITEMS;
    }

    /**
     * Persist the batch's cumulative Crafting XP using the factual XP reported by this attempt.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  int  $xpGained  The factual XP gained by this attempt.
     * @return void This method does not return a value.
     */
    private function persistXpGained(BatchCrafting $batchCrafting, int $xpGained): void
    {
        $progress = $batchCrafting->fresh()->progress;
        $progress['crafting_xp_gained'] += $xpGained;
        $batchCrafting->update(['progress' => $progress]);
    }
}
