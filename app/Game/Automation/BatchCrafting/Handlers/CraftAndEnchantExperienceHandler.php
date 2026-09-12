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
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantBatchAttemptService;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantExperienceKeepBestService;
use App\Game\Automation\BatchCrafting\Services\CraftAndEnchantExperienceTargetService;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Skills\Services\EnchantingService;

#[HandlesBatchCraftingMode(BatchCraftingType::CRAFT_AND_ENCHANT, CraftAndEnchantBatchMode::EXPERIENCE)]
class CraftAndEnchantExperienceHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly CraftAndEnchantExperienceTargetService $craftAndEnchantExperienceTargetService,
        private readonly EnchantingService $enchantingService,
        private readonly CraftAndEnchantBatchAttemptService $craftAndEnchantBatchAttemptService,
        private readonly CraftAndEnchantExperienceKeepBestService $craftAndEnchantExperienceKeepBestService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
    ) {}

    /**
     * Execute one Craft and Enchant For Experience Batch Crafting operation for the running batch.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;
        $target = $this->craftAndEnchantExperienceTargetService->resolveNext($character, $progress['cycle_position']);

        if (is_null($target)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT);
        }

        $affixes = $this->enchantingService->findMeaningfulBatchAffixes($character);

        if (is_null($affixes['prefix']) && is_null($affixes['suffix'])) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_ENCHANTING_AFFIX);
        }

        if ($affixes['intelligence_blocked']) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::INT_TOO_LOW);
        }

        $enchantingSkillXpBefore = $this->enchantingService->findEnchantingSkill($character)?->xp ?? 0;

        $progress['cycle_position'] = $target->nextCyclePosition;
        $progress['current_item_id'] = $target->item->id;
        $progress['current_item_name'] = $target->item->affix_name ?? $target->item->name;
        $progress['current_crafting_type'] = $target->craftingSkillGroup->value;
        $progress['current_prefix_name'] = $affixes['prefix']?->name;
        $progress['current_suffix_name'] = $affixes['suffix']?->name;
        $batchCrafting->update(['progress' => $progress]);

        $craftAndEnchant = $this->craftAndEnchantBatchAttemptService->craftAndEnchant(
            $character,
            $target->item,
            $target->craftingSkillGroup->value,
            $affixes['prefix']?->id,
            $affixes['suffix']?->id,
        );

        $enchantingXpGained = max(0, ($this->enchantingService->findEnchantingSkill($character)?->xp ?? 0) - $enchantingSkillXpBefore);
        $this->persistXpGained($batchCrafting, $craftAndEnchant['xp_gained'], $enchantingXpGained);

        if (! $craftAndEnchant['success']) {
            return $craftAndEnchant['result'];
        }

        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $result = $this->applyDisposition($batchCrafting, $character, $disposition, $craftAndEnchant['item'], $craftAndEnchant['gold_cost']);

        return $result->withXpGained($craftAndEnchant['xp_gained']);
    }

    /**
     * Apply the run's selected Experience disposition to the finished enchanted item.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @param BatchCraftingDisposition $disposition The selected Experience disposition.
     * @param Item $enchantedItem The finished enchanted item.
     * @param int $goldCost The combined Gold cost of this crafting and enchanting attempt.
     * @return BatchCraftingOperationResult The outcome of applying the disposition.
     */
    private function applyDisposition(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, Item $enchantedItem, int $goldCost): BatchCraftingOperationResult
    {
        if ($disposition->keepsBest()) {
            return $this->craftAndEnchantExperienceKeepBestService->apply($batchCrafting, $character, $enchantedItem, $disposition, $goldCost);
        }

        $placeItem = null;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $resolvedDestination = $this->craftingBatchAttemptService->resolveRetainedDestination($character, 'crafted_items_set');

            if ($resolvedDestination instanceof BatchCraftingEndReason) {
                return BatchCraftingOperationResult::ended($resolvedDestination);
            }

            $placeItem = $resolvedDestination;
        }

        $listingPrice = $batchCrafting->fresh()->progress['listing_price'] ?? null;

        return $this->craftAndEnchantBatchAttemptService->applyDisposition($character, $disposition, $enchantedItem, $placeItem, $listingPrice, $goldCost);
    }

    /**
     * Persist the batch's cumulative Crafting and Enchanting XP using this attempt's factual XP gained.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param int $craftingXpGained The factual Crafting XP gained by this attempt.
     * @param int $enchantingXpGained The factual Enchanting XP gained by this attempt.
     * @return void This method does not return a value.
     */
    private function persistXpGained(BatchCrafting $batchCrafting, int $craftingXpGained, int $enchantingXpGained): void
    {
        $progress = $batchCrafting->fresh()->progress;
        $progress['crafting_xp_gained'] += $craftingXpGained;
        $progress['enchanting_xp_gained'] += $enchantingXpGained;
        $batchCrafting->update(['progress' => $progress]);
    }
}
