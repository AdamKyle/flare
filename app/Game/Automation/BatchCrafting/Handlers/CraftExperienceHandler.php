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
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Enums\CraftingBatchMode;
use App\Game\Automation\BatchCrafting\Services\CraftExperienceKeepBestService;
use App\Game\Automation\BatchCrafting\Services\CraftExperienceTargetService;
use App\Game\Automation\BatchCrafting\Services\CraftingBatchAttemptService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Automation\BatchCrafting\Values\CraftExperienceCycleTarget;
use App\Game\Skills\Services\CraftingService;
use App\Game\Skills\Values\CraftingMessageMode;
use App\Game\Skills\Values\CraftingSkillGroup;
use Illuminate\Support\Collection as SupportCollection;

#[HandlesBatchCraftingMode(BatchCraftingType::CRAFT, CraftingBatchMode::EXPERIENCE)]
class CraftExperienceHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly CraftingService $craftingService,
        private readonly CraftingBatchAttemptService $craftingBatchAttemptService,
        private readonly CraftExperienceKeepBestService $craftExperienceKeepBestService,
        private readonly CraftExperienceTargetService $craftExperienceTargetService,
    ) {}

    /**
     * Execute one Craft For Experience Batch Crafting operation for the running batch.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;
        $skills = $this->craftExperienceTargetService->resolveCraftingSkills($character);
        $resolved = $this->craftExperienceTargetService->resolveNextTarget($skills, $progress['cycle_position']);

        if (is_null($resolved)) {
            return BatchCraftingOperationResult::ended($this->resolveNoTargetReason($skills));
        }

        $target = $resolved->target;
        $item = $resolved->item;

        $progress['cycle_position'] = ($resolved->index + 1) % $this->craftExperienceTargetService->cycleSize();
        $progress['current_item_id'] = $item->id;
        $progress['current_item_name'] = $item->affix_name ?? $item->name;
        $progress['current_crafting_type'] = $target->skillGroup->value;
        $batchCrafting->update(['progress' => $progress]);

        $goldCost = $this->craftingBatchAttemptService->goldCostFor($character, $item);
        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);

        return $this->performAttempt($batchCrafting, $character, $disposition, $item, $target, $goldCost);
    }

    /**
     * Perform the craft attempt for the resolved target, routing through Keep Best when selected.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @param BatchCraftingDisposition $disposition The configured crafting disposition.
     * @param Item $item The resolved item to craft.
     * @param CraftExperienceCycleTarget $target The resolved cycle target.
     * @param int $goldCost The Gold cost of this attempt.
     * @return BatchCraftingOperationResult The outcome of the craft attempt.
     */
    private function performAttempt(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, Item $item, CraftExperienceCycleTarget $target, int $goldCost): BatchCraftingOperationResult
    {
        if ($disposition->keepsBest()) {
            return $this->performKeepBestAttempt($batchCrafting, $character, $disposition, $item, $target->skillGroup, $goldCost);
        }

        $placeItem = null;

        if ($disposition === BatchCraftingDisposition::KEEP) {
            $resolvedDestination = $this->craftingBatchAttemptService->resolveRetainedDestination($character, 'crafted_items_set');

            if ($resolvedDestination instanceof BatchCraftingEndReason) {
                return BatchCraftingOperationResult::ended($resolvedDestination);
            }

            $placeItem = $resolvedDestination;
        }

        $result = $this->craftingBatchAttemptService->attempt($character, $disposition, $item, $target->craftingType, $goldCost, $placeItem);

        $this->persistXpGained($batchCrafting, $result->xpGained());

        return $result;
    }

    /**
     * Perform the craft attempt and apply the Keep Best comparison for the resolved target.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @param BatchCraftingDisposition $disposition The selected Keep Best disposition.
     * @param Item $item The resolved item to craft.
     * @param CraftingSkillGroup $skillGroup The resolved target's skill group.
     * @param int $goldCost The Gold cost of this attempt.
     * @return BatchCraftingOperationResult The outcome of the craft attempt.
     */
    private function performKeepBestAttempt(BatchCrafting $batchCrafting, Character $character, BatchCraftingDisposition $disposition, Item $item, CraftingSkillGroup $skillGroup, int $goldCost): BatchCraftingOperationResult
    {
        $craftResult = $this->craftingService->craftForBatch($character, $item, $skillGroup->value, CraftingMessageMode::BATCH_CRAFTING);

        if (! $craftResult['success']) {
            return $this->craftingBatchAttemptService->translateFailure($craftResult['reason'], $goldCost);
        }

        $this->persistXpGained($batchCrafting, $craftResult['xp_gained']);

        return $this->craftExperienceKeepBestService->apply($batchCrafting, $character, $craftResult['item'], $disposition, $goldCost);
    }

    /**
     * Persist the batch's cumulative Crafting XP using the factual XP reported by this attempt.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param int $xpGained The factual XP gained by this attempt.
     * @return void This method does not return a value.
     */
    private function persistXpGained(BatchCrafting $batchCrafting, int $xpGained): void
    {
        $progress = $batchCrafting->fresh()->progress;
        $progress['crafting_xp_gained'] += $xpGained;
        $batchCrafting->update(['progress' => $progress]);
    }

    /**
     * Resolve the factual reason no Experience cycle target is currently actionable.
     *
     * @param SupportCollection<string, Skill|null> $skills The character's already-resolved Crafting skills.
     * @return BatchCraftingEndReason SKILL_MAXED when all four Crafting skills are maxed, otherwise MAXED_OR_NOTHING_LEFT.
     */
    private function resolveNoTargetReason(SupportCollection $skills): BatchCraftingEndReason
    {
        if ($this->craftExperienceTargetService->allSkillsMaxed($skills)) {
            return BatchCraftingEndReason::SKILL_MAXED;
        }

        return BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT;
    }
}
