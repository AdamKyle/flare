<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;

class TrinketryKeepBestService
{
    public function __construct(
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
    ) {}

    /**
     * Apply the Keep Best Trinketry comparison to a newly crafted Trinket.
     *
     * Retains only the single highest-requirement Trinket produced so far in the Crafted
     * Items Set, destroying whichever result (the new one, or the previously retained one)
     * is no longer the strongest.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record, whose progress is updated and persisted.
     * @param  Character  $character  The character running the batch.
     * @param  Item  $craftedItem  The Trinket just successfully crafted.
     * @return BatchCraftingOperationResult The outcome of applying the Keep Best comparison.
     */
    public function apply(BatchCrafting $batchCrafting, Character $character, Item $craftedItem): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->fresh()->progress;
        $currentBest = $progress['trinketry_kept_best'] ?? null;
        $newQuality = $craftedItem->skill_level_required;

        if (! is_null($currentBest) && $currentBest['item_id'] === $craftedItem->id) {
            return BatchCraftingOperationResult::kept(0);
        }

        if (! is_null($currentBest) && $currentBest['quality'] > $newQuality) {
            $this->craftingBatchAttemptService->destroyForDisplacement($character, $craftedItem);

            return BatchCraftingOperationResult::destroyed(0);
        }

        if (! is_null($currentBest)) {
            $replacement = $this->batchCraftingSetService->replaceItemInSlot($character, $currentBest['set_slot_id'], $currentBest['item_id'], $craftedItem);

            if ($replacement['success'] && ! is_null($replacement['set_slot'])) {
                $this->recordBest($batchCrafting, $progress, $craftedItem, $newQuality, $replacement['set_slot']->id);
                $this->craftingBatchAttemptService->destroyForDisplacement($character, $replacement['displaced_item']);

                return BatchCraftingOperationResult::kept(0);
            }
        }

        $placement = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $craftedItem);

        if (! $placement['success'] || is_null($placement['set_slot'])) {
            return BatchCraftingOperationResult::failedAndEnded(BatchCraftingEndReason::FAILED, 0);
        }

        $this->recordBest($batchCrafting, $progress, $craftedItem, $newQuality, $placement['set_slot']->id);

        return BatchCraftingOperationResult::kept(0);
    }

    /**
     * Persist the retained best Trinket entry.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record, whose progress is updated and persisted.
     * @param  array  $progress  The batch's current progress payload.
     * @param  Item  $craftedItem  The Trinket now retained as best.
     * @param  int  $quality  The retained item's quality.
     * @param  int  $setSlotId  The Crafted Items Set slot id holding the retained item.
     * @return void This method does not return a value.
     */
    private function recordBest(BatchCrafting $batchCrafting, array $progress, Item $craftedItem, int $quality, int $setSlotId): void
    {
        $progress['trinketry_kept_best'] = ['item_id' => $craftedItem->id, 'set_slot_id' => $setSlotId, 'quality' => $quality];
        $batchCrafting->update(['progress' => $progress]);
    }
}
