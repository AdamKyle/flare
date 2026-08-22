<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantSetPhase;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantBatchAttemptService;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Automation\BatchCrafting\Values\CraftAndEnchantSetPlanEntry;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\EnchantingService;
use App\Game\Skills\Values\CraftingMessageMode;

#[HandlesBatchCraftingMode(BatchCraftingType::CRAFT_AND_ENCHANT, CraftAndEnchantBatchMode::SET)]
class CraftAndEnchantSetHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly EnchantingService $enchantingService,
        private readonly CraftAndEnchantBatchAttemptService $craftAndEnchantBatchAttemptService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
    ) {}

    /**
     * Execute one Craft and Enchant Set Batch Crafting phase step for the running batch.
     *
     * One queue position spans two consecutive handler calls: a crafting phase that produces
     * the exact base item, and an enchanting phase that applies the exact requested affixes
     * and, only once both succeed, applies the final disposition and advances the queue.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the phase step.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;
        $queue = $progress['set_queue'];
        $index = $progress['set_index'];

        if ($index >= count($queue)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::CRAFT_SET_COMPLETE);
        }

        $entry = CraftAndEnchantSetPlanEntry::fromArray($queue[$index]);

        if ($progress['set_phase'] === CraftAndEnchantSetPhase::ENCHANTING->value) {
            return $this->handleEnchantingPhase($batchCrafting, $character, $entry, $index, count($queue));
        }

        return $this->handleCraftingPhase($batchCrafting, $character, $entry);
    }

    /**
     * Craft the current queue entry's exact base item and advance to the enchanting phase.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @param  CraftAndEnchantSetPlanEntry  $entry  The current queue entry.
     * @return BatchCraftingOperationResult The outcome of the crafting phase step.
     */
    private function handleCraftingPhase(BatchCrafting $batchCrafting, Character $character, CraftAndEnchantSetPlanEntry $entry): BatchCraftingOperationResult
    {
        $item = $this->craftingService->findCraftableItemForAutomation($character, $entry->itemId);

        if (is_null($item)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT);
        }

        $craftGoldCost = $this->craftingService->getItemCostForAutomation($character, $item);
        $craftResult = $this->craftingService->craftForBatch($character, $item, $entry->craftingType, CraftingMessageMode::BATCH_CRAFTING);

        if (! $craftResult['success']) {
            return $this->craftingBatchAttemptService->translateFailure($craftResult['reason'], $craftGoldCost);
        }

        $craftedItem = $craftResult['item'];
        $progress = $batchCrafting->fresh()->progress;
        $progress['set_phase'] = CraftAndEnchantSetPhase::ENCHANTING->value;
        $progress['current_position'] = $entry->position->value;
        $progress['current_item_id'] = $craftedItem->id;
        $progress['current_item_name'] = $entry->itemName;
        $progress['pending_craft_gold_cost'] = $craftGoldCost;
        $batchCrafting->update(['progress' => $progress]);

        return BatchCraftingOperationResult::inProgress();
    }

    /**
     * Apply the current queue entry's exact requested affixes and, when complete, the final disposition.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @param  CraftAndEnchantSetPlanEntry  $entry  The current queue entry.
     * @param  int  $index  The current queue index.
     * @param  int  $queueLength  The total number of queued positions.
     * @return BatchCraftingOperationResult The outcome of the enchanting phase step.
     */
    private function handleEnchantingPhase(BatchCrafting $batchCrafting, Character $character, CraftAndEnchantSetPlanEntry $entry, int $index, int $queueLength): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->fresh()->progress;
        $craftGoldCost = $progress['pending_craft_gold_cost'] ?? 0;
        $pendingItem = Item::find($progress['current_item_id']);

        if (is_null($pendingItem)) {
            $this->resetToCraftingPhase($batchCrafting, $progress);

            return BatchCraftingOperationResult::failed($craftGoldCost);
        }

        $affixes = $this->enchantingService->resolveBatchAffixes($character, $entry->prefixId, $entry->suffixId);

        if (! is_null($affixes['error'])) {
            $this->resetToCraftingPhase($batchCrafting, $progress);

            return BatchCraftingOperationResult::failed($craftGoldCost);
        }

        $progress['current_prefix_name'] = $affixes['prefix']?->name;
        $progress['current_suffix_name'] = $affixes['suffix']?->name;
        $batchCrafting->update(['progress' => $progress]);

        $affixIds = array_values(array_filter([$affixes['prefix']?->id, $affixes['suffix']?->id]));
        $enchantGoldCost = $this->enchantingService->getCostOfEnchantment($character, $affixIds, $pendingItem->id);
        $enchantResult = $this->enchantingService->enchantItemForBatch($character, $pendingItem, $affixIds, $enchantGoldCost, true);
        $totalGoldCost = $craftGoldCost + $enchantGoldCost;

        if (! $enchantResult['success']) {
            $this->resetToCraftingPhase($batchCrafting, $batchCrafting->fresh()->progress);

            return BatchCraftingOperationResult::failed($totalGoldCost);
        }

        return $this->completePosition($batchCrafting, $character, $enchantResult['item'], $totalGoldCost, $index, $queueLength);
    }

    /**
     * Apply the final disposition to the fully enchanted item and advance the queue.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @param  Item  $enchantedItem  The finished enchanted item.
     * @param  int  $goldCost  The combined crafting and enchanting Gold cost for this position.
     * @param  int  $index  The current queue index.
     * @param  int  $queueLength  The total number of queued positions.
     * @return BatchCraftingOperationResult The outcome of the completed position.
     */
    private function completePosition(BatchCrafting $batchCrafting, Character $character, Item $enchantedItem, int $goldCost, int $index, int $queueLength): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->fresh()->progress;
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $placeItem = null;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $resolvedDestination = $this->craftingBatchAttemptService->resolveRetainedDestination($character, $progress['output_destination'], $progress['output_set_id'] ?? null);

            if ($resolvedDestination instanceof BatchCraftingEndReason) {
                return BatchCraftingOperationResult::ended($resolvedDestination);
            }

            $placeItem = $resolvedDestination;
        }

        $result = $this->craftAndEnchantBatchAttemptService->applyDisposition($character, $disposition, $enchantedItem, $placeItem, $progress['listing_price'] ?? null, $goldCost);

        $nextIndex = $index + 1;
        $progress = $batchCrafting->fresh()->progress;
        $progress['set_index'] = $nextIndex;
        $this->clearTransientState($progress);
        $batchCrafting->update(['progress' => $progress]);

        if ($nextIndex >= $queueLength) {
            return $result->withEndReason(BatchCraftingEndReason::CRAFT_SET_COMPLETE);
        }

        return $result;
    }

    /**
     * Reset the current queue position back to its crafting phase, clearing all transient state.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  array  $progress  The batch's current progress payload.
     * @return void This method does not return a value.
     */
    private function resetToCraftingPhase(BatchCrafting $batchCrafting, array $progress): void
    {
        $progress['set_phase'] = CraftAndEnchantSetPhase::CRAFTING->value;
        $this->clearTransientState($progress);
        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Clear the transient current-item/affix progress fields, in place.
     *
     * @param  array  $progress  The batch's current progress payload, modified in place.
     * @return void This method does not return a value.
     */
    private function clearTransientState(array &$progress): void
    {
        $progress['set_phase'] = CraftAndEnchantSetPhase::CRAFTING->value;
        $progress['current_position'] = null;
        $progress['current_item_id'] = null;
        $progress['current_item_name'] = null;
        $progress['current_prefix_name'] = null;
        $progress['current_suffix_name'] = null;
        unset($progress['pending_craft_gold_cost']);
    }
}
