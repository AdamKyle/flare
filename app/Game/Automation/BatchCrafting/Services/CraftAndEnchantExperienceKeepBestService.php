<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Character\CharacterInventory\Services\BatchCraftingSetService;

class CraftAndEnchantExperienceKeepBestService
{
    public function __construct(
        private readonly BatchCraftingSetService $batchCraftingSetService,
        private readonly CraftAndEnchantBatchAttemptService $craftAndEnchantBatchAttemptService,
    ) {}

    /**
     * Apply a Keep Best disposition to a newly crafted and enchanted item, keyed by its item type.
     *
     * Mirrors CraftExperienceKeepBestService's retained-best comparison, extended with a
     * Disenchant Rest disposal path for the enchanted Craft and Enchant For Experience workflow.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record, whose progress is updated and persisted.
     * @param Character $character The character running the batch.
     * @param Item $enchantedItem The item just successfully crafted and enchanted.
     * @param BatchCraftingDisposition $disposition The selected Keep Best disposition.
     * @param int $goldCost The combined Gold cost of this crafting and enchanting attempt.
     * @return BatchCraftingOperationResult The outcome of applying the Keep Best comparison.
     */
    public function apply(BatchCrafting $batchCrafting, Character $character, Item $enchantedItem, BatchCraftingDisposition $disposition, int $goldCost): BatchCraftingOperationResult
    {
        $itemType = $enchantedItem->type;
        $progress = $batchCrafting->fresh()->progress;
        $bestMap = $progress['kept_best'] ?? [];
        $currentBest = $bestMap[$itemType] ?? null;
        $newQuality = $enchantedItem->skill_level_required;

        if ($this->isSameRetainedItem($currentBest, $enchantedItem)) {
            return BatchCraftingOperationResult::kept($goldCost);
        }

        if (! is_null($currentBest) && $currentBest['quality'] > $newQuality) {
            return $this->disposeInferiorItem($character, $enchantedItem, $disposition, $goldCost);
        }

        if (! is_null($currentBest)) {
            $replacement = $this->batchCraftingSetService->replaceItemInSlot($character, $currentBest['set_slot_id'], $currentBest['item_id'], $enchantedItem);

            if ($replacement['success'] && ! is_null($replacement['set_slot'])) {
                $this->recordBest($batchCrafting, $progress, $bestMap, $itemType, $enchantedItem, $newQuality, $replacement['set_slot']->id);

                return $this->disposeDisplacedItem($character, $replacement['displaced_item'], $disposition, $goldCost);
            }
        }

        $placement = $this->batchCraftingSetService->createItemInBatchCraftingSet($character, $enchantedItem);

        if (! $placement['success'] || is_null($placement['set_slot'])) {
            return BatchCraftingOperationResult::failedAndEnded(BatchCraftingEndReason::FAILED, $goldCost);
        }

        $this->recordBest($batchCrafting, $progress, $bestMap, $itemType, $enchantedItem, $newQuality, $placement['set_slot']->id);

        return BatchCraftingOperationResult::kept($goldCost);
    }

    /**
     * Persist the retained best entry for an item type.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record, whose progress is updated and persisted.
     * @param array $progress The batch's current progress payload.
     * @param array $bestMap The current retained-best map, keyed by item type.
     * @param string $itemType The item type being recorded.
     * @param Item $enchantedItem The item now retained as best for this item type.
     * @param int $quality The retained item's quality.
     * @param int $setSlotId The Crafted Items Set slot id holding the retained item.
     * @return void This method does not return a value.
     */
    private function recordBest(BatchCrafting $batchCrafting, array $progress, array $bestMap, string $itemType, Item $enchantedItem, int $quality, int $setSlotId): void
    {
        $bestMap[$itemType] = ['item_id' => $enchantedItem->id, 'set_slot_id' => $setSlotId, 'quality' => $quality];
        $progress['kept_best'] = $bestMap;
        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Determine whether the newly crafted item is the exact same item already retained as best.
     *
     * @param array{item_id: int, set_slot_id: int, quality: int}|null $currentBest The currently retained best entry, if any.
     * @param Item $enchantedItem The item just successfully crafted and enchanted.
     * @return bool True when the retained best already holds this same item.
     */
    private function isSameRetainedItem(?array $currentBest, Item $enchantedItem): bool
    {
        return ! is_null($currentBest) && $currentBest['item_id'] === $enchantedItem->id;
    }

    /**
     * Dispose of a newly crafted item that is not stronger than the currently retained best.
     *
     * @param Character $character The character running the batch.
     * @param Item $enchantedItem The inferior newly crafted and enchanted item.
     * @param BatchCraftingDisposition $disposition The selected Keep Best disposition.
     * @param int $goldCost The combined Gold cost of this crafting and enchanting attempt.
     * @return BatchCraftingOperationResult The outcome recording the disposed item.
     */
    private function disposeInferiorItem(Character $character, Item $enchantedItem, BatchCraftingDisposition $disposition, int $goldCost): BatchCraftingOperationResult
    {
        if ($disposition === BatchCraftingDisposition::KEEP_BEST_SELL_REST) {
            $goldGained = $this->craftAndEnchantBatchAttemptService->sellForDisplacement($character, $enchantedItem);

            return BatchCraftingOperationResult::sold($goldCost, $goldGained);
        }

        if ($disposition === BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST) {
            $this->craftAndEnchantBatchAttemptService->disenchantForDisplacement($character);

            return BatchCraftingOperationResult::disenchanted($goldCost);
        }

        $this->craftAndEnchantBatchAttemptService->destroyForDisplacement($character, $enchantedItem);

        return BatchCraftingOperationResult::destroyed($goldCost);
    }

    /**
     * Dispose of the item displaced by an in-place Crafted Items Set slot replacement.
     *
     * @param Character $character The character running the batch.
     * @param Item $displacedItem The item that was displaced from the retained slot.
     * @param BatchCraftingDisposition $disposition The selected Keep Best disposition.
     * @param int $goldCost The combined Gold cost of the new best item's crafting and enchanting attempt.
     * @return BatchCraftingOperationResult The outcome recording the displacement.
     */
    private function disposeDisplacedItem(Character $character, Item $displacedItem, BatchCraftingDisposition $disposition, int $goldCost): BatchCraftingOperationResult
    {
        if ($disposition === BatchCraftingDisposition::KEEP_BEST_SELL_REST) {
            $goldGained = $this->craftAndEnchantBatchAttemptService->sellForDisplacement($character, $displacedItem);

            return BatchCraftingOperationResult::keptWithDisplacedSale($goldCost, $goldGained);
        }

        if ($disposition === BatchCraftingDisposition::KEEP_BEST_DISENCHANT_REST) {
            $this->craftAndEnchantBatchAttemptService->disenchantForDisplacement($character);

            return BatchCraftingOperationResult::keptWithDisplacedDisenchant($goldCost);
        }

        $this->craftAndEnchantBatchAttemptService->destroyForDisplacement($character, $displacedItem);

        return BatchCraftingOperationResult::keptWithDisplacedDestroy($goldCost);
    }
}
