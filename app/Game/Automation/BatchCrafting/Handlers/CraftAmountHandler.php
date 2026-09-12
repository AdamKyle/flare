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
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Skills\Services\CraftingService;

#[HandlesBatchCraftingMode(BatchCraftingType::CRAFT, CraftingBatchMode::AMOUNT)]
class CraftAmountHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
    ) {}

    /**
     * Execute one Craft Amount Batch Crafting operation for the running batch.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;

        if ($this->hasReachedRequestedAmount($progress)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        $item = $this->findCraftableItem($character, $progress);

        if (is_null($item)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT);
        }

        $craftingType = $progress['specific_crafting_type'];
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $goldCost = $this->craftingBatchAttemptService->goldCostFor($character, $item);

        $placeItem = null;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $resolvedDestination = $this->craftingBatchAttemptService->resolveRetainedDestination($character, $progress['output_destination']);

            if ($resolvedDestination instanceof BatchCraftingEndReason) {
                return BatchCraftingOperationResult::ended($resolvedDestination);
            }

            $placeItem = $resolvedDestination;
        }

        $result = $this->craftingBatchAttemptService->attempt($character, $disposition, $item, $craftingType, $goldCost, $placeItem);

        if (! $result->didCraft()) {
            return $result;
        }

        $progress['craft_specific_count']++;
        $batchCrafting->update(['progress' => $progress]);

        if ($progress['craft_specific_count'] >= $progress['craft_amount']) {
            return $result->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        return $result;
    }

    /**
     * Determine whether the Craft Amount run has already reached its requested amount.
     *
     * @param array $progress The persisted Craft Amount progress data.
     * @return bool True when the requested amount has been reached.
     */
    private function hasReachedRequestedAmount(array $progress): bool
    {
        return $progress['craft_specific_count'] >= $progress['craft_amount'];
    }

    /**
     * Find the character's craftable item matching the persisted Craft Amount progress.
     *
     * @param Character $character The character running the batch.
     * @param array $progress The persisted Craft Amount progress data.
     * @return Item|null The matching craftable item, or null when unavailable.
     */
    private function findCraftableItem(Character $character, array $progress): ?Item
    {
        return $this->craftingService->findCraftableItemForAutomation($character, $progress['specific_item_id']);
    }
}
