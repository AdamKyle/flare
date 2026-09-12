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
use App\Game\Automation\BatchCrafting\Values\CraftSetPlanEntry;
use App\Game\Skills\Services\CraftingService;
use Closure;

#[HandlesBatchCraftingMode(BatchCraftingType::CRAFT, CraftingBatchMode::SET)]
class CraftSetHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
    ) {}

    /**
     * Execute one Craft Set Batch Crafting operation for the running batch.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;
        $queue = $progress['set_queue'];
        $index = $progress['set_index'];

        if ($index >= count($queue)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::CRAFT_SET_COMPLETE);
        }

        $entry = CraftSetPlanEntry::fromArray($queue[$index]);
        $item = $this->resolveEntryItem($character, $entry);

        if (is_null($item)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT);
        }

        $progress['current_item_id'] = $item->id;
        $progress['current_item_name'] = $entry->itemName;
        $progress['current_crafting_type'] = $entry->craftingType;
        $batchCrafting->update(['progress' => $progress]);

        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $goldCost = $this->craftingBatchAttemptService->goldCostFor($character, $item);
        $placeItem = null;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $resolvedDestination = $this->resolveDestination($character, $progress);

            if ($resolvedDestination instanceof BatchCraftingEndReason) {
                return BatchCraftingOperationResult::ended($resolvedDestination);
            }

            $placeItem = $resolvedDestination;
        }

        $result = $this->craftingBatchAttemptService->attempt($character, $disposition, $item, $entry->craftingType, $goldCost, $placeItem);

        if (! $result->didCraft()) {
            return $result;
        }

        $nextIndex = $index + 1;
        $progress['set_index'] = $nextIndex;
        $batchCrafting->update(['progress' => $progress]);

        if ($nextIndex >= count($queue)) {
            return $result->withEndReason(BatchCraftingEndReason::CRAFT_SET_COMPLETE);
        }

        return $result;
    }

    /**
     * Re-resolve the currently planned queue entry's item, confirming it is still genuinely craftable.
     *
     * @param Character $character The character running the batch.
     * @param CraftSetPlanEntry $entry The current queue entry.
     * @return Item|null The still-craftable item, or null when it is no longer available.
     */
    private function resolveEntryItem(Character $character, CraftSetPlanEntry $entry): ?Item
    {
        return $this->craftingService->findCraftableItemForAutomation($character, $entry->itemId);
    }

    /**
     * Resolve the retained item's output destination placement callback, or a capacity end reason.
     *
     * @param Character $character The character running the batch.
     * @param array $progress The persisted Craft Set progress data.
     * @return BatchCraftingEndReason|Closure The capacity end reason, or the placement callback.
     */
    private function resolveDestination(Character $character, array $progress): BatchCraftingEndReason|Closure
    {
        return $this->craftingBatchAttemptService->resolveRetainedDestination(
            $character,
            $progress['output_destination'],
            $progress['output_set_id'] ?? null,
        );
    }
}
