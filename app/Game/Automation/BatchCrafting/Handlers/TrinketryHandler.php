<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingOutputDestination;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\TrinketryBatchMode;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Automation\BatchCrafting\Services\TrinketryKeepBestService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Services\TrinketCraftingService;

#[HandlesBatchCraftingMode(BatchCraftingType::TRINKETRY, TrinketryBatchMode::EXPERIENCE)]
class TrinketryHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly TrinketCraftingService $trinketCraftingService,
        private readonly CraftingService $craftingService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
        private readonly TrinketryKeepBestService $trinketryKeepBestService,
    ) {}

    /**
     * Execute one Trinketry Batch Crafting operation for the running batch.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $skill = $this->trinketCraftingService->findTrinketrySkill($character);
        $item = is_null($skill) ? null : $this->trinketCraftingService->findMeaningfulBatchItem($character);

        if (is_null($item)) {
            return BatchCraftingOperationResult::ended($this->resolveNoTargetReason($skill));
        }

        $cost = $this->trinketCraftingService->craftingCost($character, $item);

        if ($cost['gold_dust']['missing'] > 0) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_GOLD_DUST);
        }

        if ($cost['copper_coins']['missing'] > 0) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_COPPER_COINS);
        }

        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $xpBefore = $skill->xp;

        $result = $disposition->keepsBest()
            ? $this->handleKeepBest($batchCrafting, $character, $item)
            : $this->handleDirect($character, $item, $disposition);

        $xpGained = max(0, ($this->trinketCraftingService->findTrinketrySkill($character)?->xp ?? $xpBefore) - $xpBefore);
        $this->persistProgress($batchCrafting, $item, $xpGained);

        return $result->withResourceSpending(goldDustSpent: $cost['gold_dust']['required'], copperCoinsSpent: $cost['copper_coins']['required']);
    }

    /**
     * Craft the resolved item and apply the Keep Best comparison to the outcome.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @param Item $item The resolved Trinket to craft.
     * @return BatchCraftingOperationResult The outcome of the craft attempt.
     */
    private function handleKeepBest(BatchCrafting $batchCrafting, Character $character, Item $item): BatchCraftingOperationResult
    {
        $craftResult = $this->trinketCraftingService->craftForBatch($character, $item, true);

        if (! $craftResult['success']) {
            return BatchCraftingOperationResult::failed(0);
        }

        return $this->trinketryKeepBestService->apply($batchCrafting, $character, $craftResult['item']);
    }

    /**
     * Craft the resolved item and apply a direct Keep/Destroy disposition to the outcome.
     *
     * @param Character $character The character running the batch.
     * @param Item $item The resolved Trinket to craft.
     * @param BatchCraftingDisposition $disposition The selected direct disposition.
     * @return BatchCraftingOperationResult The outcome of the craft attempt.
     */
    private function handleDirect(Character $character, Item $item, BatchCraftingDisposition $disposition): BatchCraftingOperationResult
    {
        $placeItem = null;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $resolvedDestination = $this->craftingBatchAttemptService->resolveRetainedDestination($character, BatchCraftingOutputDestination::CRAFTED_ITEMS_SET->value);

            if ($resolvedDestination instanceof BatchCraftingEndReason) {
                return BatchCraftingOperationResult::ended($resolvedDestination);
            }

            $placeItem = $resolvedDestination;
        }

        $craftResult = $this->trinketCraftingService->craftForBatch($character, $item, true, $placeItem);

        if (! $craftResult['success']) {
            return BatchCraftingOperationResult::failed(0);
        }

        if ($disposition === BatchCraftingDisposition::DESTROY) {
            $this->craftingBatchAttemptService->destroyForDisplacement($character, $craftResult['item']);

            return BatchCraftingOperationResult::destroyed(0);
        }

        return BatchCraftingOperationResult::kept(0);
    }

    /**
     * Persist the batch's cumulative Trinketry XP and current item facts.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Item $item The resolved Trinket for this operation.
     * @param int $xpGained The factual XP gained by this operation.
     * @return void This method does not return a value.
     */
    private function persistProgress(BatchCrafting $batchCrafting, Item $item, int $xpGained): void
    {
        $progress = $batchCrafting->fresh()->progress;
        $progress['trinketry_xp_gained'] += $xpGained;
        $progress['current_item_id'] = $item->id;
        $progress['current_item_name'] = $item->affix_name ?? $item->name;
        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Resolve the factual reason no Trinketry target is currently actionable.
     *
     * @param Skill|null $skill The character's already-resolved Trinketry skill, when present.
     * @return BatchCraftingEndReason SKILL_MAXED when the Trinketry skill is maxed, otherwise NO_TRINKETRY_ITEMS.
     */
    private function resolveNoTargetReason(?Skill $skill): BatchCraftingEndReason
    {
        if (! is_null($skill) && $this->craftingService->isSkillMaxed($skill)) {
            return BatchCraftingEndReason::SKILL_MAXED;
        }

        return BatchCraftingEndReason::NO_TRINKETRY_ITEMS;
    }
}
