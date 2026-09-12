<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\HolyOilsBatchMode;
use App\Game\Automation\BatchCrafting\Services\HolyOilBatchDispositionService;
use App\Game\Automation\BatchCrafting\Services\HolyOilOilPoolResolver;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use App\Game\Npcs\Actions\WorkBench\Values\HolyOilBatchApplicationResult;

#[HandlesBatchCraftingMode(BatchCraftingType::HOLY_OILS, HolyOilsBatchMode::SELECTED_ITEMS)]
class HolyOilSelectedItemsHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly HolyItemService $holyItemService,
        private readonly HolyOilOilPoolResolver $holyOilOilPoolResolver,
        private readonly HolyOilBatchDispositionService $holyOilBatchDispositionService,
    ) {}

    /**
     * Execute one Holy Oils Selected Items Batch Crafting operation for the running batch.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;
        $plan = $progress['plan'];

        if ($progress['plan_index'] >= count($plan)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        $targetSlotId = $progress['current_target_slot_id'] ?? $plan[$progress['plan_index']]['target_slot_id'];
        $inventory = $character->inventory;
        $targetSlot = is_null($inventory) ? null : InventorySlot::where('inventory_id', $inventory->id)->where('id', $targetSlotId)->with('item')->first();

        if (is_null($targetSlot) || in_array($targetSlot->item->type, ['trinket', 'artifact'], true)) {
            return $this->advance($batchCrafting, BatchCraftingOperationResult::skipped());
        }

        if ($this->remainingCapacity($targetSlot) <= 0) {
            return $this->completeTarget($batchCrafting, $character, $targetSlot, $progress, 0);
        }

        $oilSlot = $this->holyOilOilPoolResolver->nextAvailableOil($character, $progress['oil_slot_ids']);

        if (is_null($oilSlot)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_HOLY_OILS);
        }

        $applicationResult = $this->holyItemService->applyOilForBatch($character, $targetSlot->id, $oilSlot->id);

        if (! $applicationResult->success) {
            return $this->handleApplicationFailure($batchCrafting, $applicationResult);
        }

        $resultingSlot = $applicationResult->resultingSlot;
        $this->updateCurrentFacts($batchCrafting, $resultingSlot, $oilSlot);

        if ($applicationResult->saturated) {
            return $this->completeTarget($batchCrafting, $character, $resultingSlot, $batchCrafting->fresh()->progress, $applicationResult->goldDustSpent);
        }

        return BatchCraftingOperationResult::applied()->withResourceSpending(goldDustSpent: $applicationResult->goldDustSpent);
    }

    /**
     * Determine the target item's remaining Holy stack capacity.
     *
     * @param InventorySlot $slot The target slot.
     * @return int The remaining Holy stack capacity.
     */
    private function remainingCapacity(InventorySlot $slot): int
    {
        return $slot->item->holy_stacks - $slot->item->holy_stacks_applied;
    }

    /**
     * Persist the current target/oil/stack facts after a successful application.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param InventorySlot $resultingSlot The resulting target slot after the application.
     * @param AlchemyBagSlot $oilSlot The Holy Oil slot consumed by the application.
     * @return void This method does not return a value.
     */
    private function updateCurrentFacts(BatchCrafting $batchCrafting, InventorySlot $resultingSlot, AlchemyBagSlot $oilSlot): void
    {
        $progress = $batchCrafting->fresh()->progress;
        $progress['current_target_slot_id'] = $resultingSlot->id;
        $progress['current_target_item_id'] = $resultingSlot->item->id;
        $progress['current_target_item_name'] = $resultingSlot->item->affix_name ?? $resultingSlot->item->name;
        $progress['current_oil_item_id'] = $oilSlot->item->id;
        $progress['current_oil_item_name'] = $oilSlot->item->affix_name ?? $oilSlot->item->name;
        $progress['current_holy_stacks'] = $resultingSlot->item->holy_stacks_applied;
        $progress['max_holy_stacks'] = $resultingSlot->item->holy_stacks;
        $progress['applications_completed'] = ($progress['applications_completed'] ?? 0) + 1;
        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Apply the selected disposition to a completed target and advance the plan.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @param InventorySlot $slot The completed target slot.
     * @param array $progress The persisted Batch Crafting progress data.
     * @param int $goldDustSpentThisTick The Gold Dust spent applying this tick's final oil, when applicable.
     * @return BatchCraftingOperationResult The outcome of the disposition transition.
     */
    private function completeTarget(BatchCrafting $batchCrafting, Character $character, InventorySlot $slot, array $progress, int $goldDustSpentThisTick): BatchCraftingOperationResult
    {
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $listingPrice = $progress['listing_price'] ?? null;
        $result = $this->holyOilBatchDispositionService->apply($character, $disposition, $slot, $listingPrice);

        return $this->advance($batchCrafting, $result)->withResourceSpending(goldDustSpent: $goldDustSpentThisTick);
    }

    /**
     * Translate a failed Holy Oil application into the appropriate outcome.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param HolyOilBatchApplicationResult $applicationResult The failed application outcome.
     * @return BatchCraftingOperationResult The translated outcome.
     */
    private function handleApplicationFailure(BatchCrafting $batchCrafting, HolyOilBatchApplicationResult $applicationResult): BatchCraftingOperationResult
    {
        return match ($applicationResult->reason) {
            'not_enough_gold_dust' => BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_GOLD_DUST),
            'invalid_oil' => BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_HOLY_OILS),
            default => $this->advance($batchCrafting, BatchCraftingOperationResult::skipped()),
        };
    }

    /**
     * Advance the plan to its next target, resetting the current target/oil/stack facts.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param BatchCraftingOperationResult $result The outcome reached before advancing.
     * @return BatchCraftingOperationResult The outcome, carrying an end reason when the plan is now exhausted.
     */
    private function advance(BatchCrafting $batchCrafting, BatchCraftingOperationResult $result): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->fresh()->progress;
        $plan = $progress['plan'];
        $nextIndex = $progress['plan_index'] + 1;

        $progress['plan_index'] = $nextIndex;
        $progress['current_target_slot_id'] = $plan[$nextIndex]['target_slot_id'] ?? null;
        $progress['current_target_item_id'] = null;
        $progress['current_target_item_name'] = null;
        $progress['current_oil_item_id'] = null;
        $progress['current_oil_item_name'] = null;
        $progress['current_holy_stacks'] = null;
        $progress['max_holy_stacks'] = null;
        $batchCrafting->update(['progress' => $progress]);

        if ($nextIndex >= count($plan)) {
            return $result->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        return $result;
    }
}
