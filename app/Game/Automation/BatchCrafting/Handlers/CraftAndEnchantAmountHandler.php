<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftAndEnchantBatchMode;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantBatchAttemptService;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Skills\Services\CraftingService;

#[HandlesBatchCraftingMode(BatchCraftingType::CRAFT_AND_ENCHANT, CraftAndEnchantBatchMode::AMOUNT)]
class CraftAndEnchantAmountHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
        private readonly CraftAndEnchantBatchAttemptService $craftAndEnchantBatchAttemptService,
    ) {}

    /**
     * Execute one Craft and Enchant Amount Batch Crafting operation for the running batch.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;

        if ($progress['completed_amount'] >= $progress['craft_amount']) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        $item = $this->craftingService->findCraftableItemForAutomation($character, $progress['specific_item_id']);

        if (is_null($item)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT);
        }

        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $placeItem = null;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $resolvedDestination = $this->craftingBatchAttemptService->resolveRetainedDestination($character, $progress['output_destination'], $progress['output_set_id']);

            if ($resolvedDestination instanceof BatchCraftingEndReason) {
                return BatchCraftingOperationResult::ended($resolvedDestination);
            }

            $placeItem = $resolvedDestination;
        }

        $result = $this->craftAndEnchantBatchAttemptService->attempt(
            $character,
            $disposition,
            $item,
            $progress['specific_crafting_type'],
            $progress['prefix_id'],
            $progress['suffix_id'],
            $placeItem,
            $progress['listing_price'],
        );

        if (! $result->didCraft()) {
            return $result;
        }

        $progress = $batchCrafting->fresh()->progress;
        $progress['completed_amount']++;
        $batchCrafting->update(['progress' => $progress]);

        if ($progress['completed_amount'] >= $progress['craft_amount']) {
            return $result->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        return $result;
    }
}
