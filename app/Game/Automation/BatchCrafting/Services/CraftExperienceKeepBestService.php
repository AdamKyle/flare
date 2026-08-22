<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;

class CraftExperienceKeepBestService
{
    public function __construct(
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
    ) {}

    /**
     * Apply a Keep Best disposition to a newly crafted item, keyed by its actual item type.
     *
     * Retains the strongest item crafted for each item type in the Crafted Items Set and
     * disposes of the inferior/replaced result according to the selected Keep Best disposition.
     * A helmet never competes against a body armour result, and a Damage Spell never competes
     * against a Healing Spell result, because the retained-best key is the crafted item's own
     * item type rather than its broader Crafting skill group.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record, whose progress is updated and persisted.
     * @param  Character  $character  The character running the batch.
     * @param  Item  $craftedItem  The item just successfully crafted.
     * @param  BatchCraftingDisposition  $disposition  The selected Keep Best disposition.
     * @param  int  $goldCost  The Gold cost of this crafting attempt.
     * @return BatchCraftingOperationResult The outcome of applying the Keep Best comparison.
     */
    public function apply(BatchCrafting $batchCrafting, Character $character, Item $craftedItem, BatchCraftingDisposition $disposition, int $goldCost): BatchCraftingOperationResult
    {
        $itemType = $craftedItem->type;
        $progress = $batchCrafting->fresh()->progress;
        $bestMap = $progress['kept_best'] ?? [];
        $currentBest = $bestMap[$itemType] ?? null;
        $newQuality = $craftedItem->skill_level_required;

        if ($this->isSameRetainedItem($currentBest, $craftedItem)) {
            return BatchCraftingOperationResult::kept($goldCost);
        }

        if (! is_null($currentBest) && $currentBest['quality'] > $newQuality) {
            return $this->disposeInferiorItem($character, $craftedItem, $disposition, $goldCost);
        }

        if (! is_null($currentBest)) {
            $replacement = $this->batchCraftingSetService->replaceItemInSlot($character, $currentBest['set_slot_id'], $currentBest['item_id'], $craftedItem);

            if ($replacement['success'] && ! is_null($replacement['set_slot'])) {
                $this->recordBest($batchCrafting, $progress, $bestMap, $itemType, $craftedItem, $newQuality, $replacement['set_slot']->id);

                return $this->disposeDisplacedItem($character, $replacement['displaced_item'], $disposition, $goldCost);
            }
        }

        $placement = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $craftedItem);

        if (! $placement['success'] || is_null($placement['set_slot'])) {
            return BatchCraftingOperationResult::failedAndEnded(BatchCraftingEndReason::FAILED, $goldCost);
        }

        $this->recordBest($batchCrafting, $progress, $bestMap, $itemType, $craftedItem, $newQuality, $placement['set_slot']->id);

        return BatchCraftingOperationResult::kept($goldCost);
    }

    /**
     * Persist the retained best entry for an item type.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record, whose progress is updated and persisted.
     * @param  array  $progress  The batch's current progress payload.
     * @param  array  $bestMap  The current retained-best map, keyed by item type.
     * @param  string  $itemType  The item type being recorded.
     * @param  Item  $craftedItem  The item now retained as best for this item type.
     * @param  int  $quality  The retained item's quality.
     * @param  int  $setSlotId  The Crafted Items Set slot id holding the retained item.
     */
    private function recordBest(BatchCrafting $batchCrafting, array $progress, array $bestMap, string $itemType, Item $craftedItem, int $quality, int $setSlotId): void
    {
        $bestMap[$itemType] = ['item_id' => $craftedItem->id, 'set_slot_id' => $setSlotId, 'quality' => $quality];
        $progress['kept_best'] = $bestMap;
        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Determine whether the newly crafted item is the exact same item already retained as best.
     *
     * @param  array{item_id: int, set_slot_id: int, quality: int}|null  $currentBest  The currently retained best entry, if any.
     * @param  Item  $craftedItem  The item just successfully crafted.
     * @return bool True when the retained best already holds this same item.
     */
    private function isSameRetainedItem(?array $currentBest, Item $craftedItem): bool
    {
        return ! is_null($currentBest) && $currentBest['item_id'] === $craftedItem->id;
    }

    /**
     * Dispose of a newly crafted item that is not stronger than the currently retained best.
     *
     * @param  Character  $character  The character running the batch.
     * @param  Item  $craftedItem  The inferior newly crafted item.
     * @param  BatchCraftingDisposition  $disposition  The selected Keep Best disposition.
     * @param  int  $goldCost  The Gold cost of this crafting attempt.
     * @return BatchCraftingOperationResult The outcome recording the disposed item.
     */
    private function disposeInferiorItem(Character $character, Item $craftedItem, BatchCraftingDisposition $disposition, int $goldCost): BatchCraftingOperationResult
    {
        if ($disposition === BatchCraftingDisposition::KEEP_BEST_SELL_REST) {
            $goldGained = $this->craftingBatchAttemptService->sellForDisplacement($character, $craftedItem);

            return BatchCraftingOperationResult::sold($goldCost, $goldGained);
        }

        $this->craftingBatchAttemptService->destroyForDisplacement($character, $craftedItem);

        return BatchCraftingOperationResult::destroyed($goldCost);
    }

    /**
     * Dispose of the item displaced by an in-place Crafted Items Set slot replacement.
     *
     * @param  Character  $character  The character running the batch.
     * @param  Item  $displacedItem  The item that was displaced from the retained slot.
     * @param  BatchCraftingDisposition  $disposition  The selected Keep Best disposition.
     * @param  int  $goldCost  The Gold cost of the new best item's crafting attempt.
     * @return BatchCraftingOperationResult The outcome recording the displacement.
     */
    private function disposeDisplacedItem(Character $character, Item $displacedItem, BatchCraftingDisposition $disposition, int $goldCost): BatchCraftingOperationResult
    {
        if ($disposition === BatchCraftingDisposition::KEEP_BEST_SELL_REST) {
            $goldGained = $this->craftingBatchAttemptService->sellForDisplacement($character, $displacedItem);

            return BatchCraftingOperationResult::keptWithDisplacedSale($goldCost, $goldGained);
        }

        $this->craftingBatchAttemptService->destroyForDisplacement($character, $displacedItem);

        return BatchCraftingOperationResult::keptWithDisplacedDestroy($goldCost);
    }
}
