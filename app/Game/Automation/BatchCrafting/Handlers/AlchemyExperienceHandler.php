<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Skill;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\AlchemyBatchDispositionService;
use App\Game\Automation\BatchCrafting\Services\AlchemyExperienceKeepBestService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Skills\Services\AlchemyService;
use App\Game\Skills\Services\CraftingService;

#[HandlesBatchCraftingMode(BatchCraftingType::ALCHEMY, AlchemyBatchMode::EXPERIENCE)]
class AlchemyExperienceHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly AlchemyService $alchemyService,
        private readonly CraftingService $craftingService,
        private readonly AlchemyBatchDispositionService $alchemyBatchDispositionService,
        private readonly AlchemyExperienceKeepBestService $alchemyExperienceKeepBestService,
    ) {}

    /**
     * Execute one Alchemy For Experience Batch Crafting operation for the running batch.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record.
     * @param  Character  $character  The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $skill = $this->alchemyService->findAlchemySkill($character);
        $item = is_null($skill) ? null : $this->alchemyService->findMeaningfulBatchItem($character);

        if (is_null($item)) {
            return BatchCraftingOperationResult::ended($this->resolveNoTargetReason($skill));
        }

        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $cost = $this->alchemyService->resolveCost($character, $item);

        if ($cost['gold_dust'] > $character->gold_dust) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_GOLD_DUST);
        }

        if ($cost['shards'] > $character->shards) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_SHARDS);
        }

        $xpBefore = $skill->xp;
        $bypassBagCapacity = $disposition !== BatchCraftingDisposition::KEEP && ! $disposition->keepsBest();

        $transmuted = $this->alchemyService->transmute($character, $item->id, true, $bypassBagCapacity);

        if (is_null($transmuted)) {
            return BatchCraftingOperationResult::failed(0)->withResourceSpending($cost['gold_dust'], $cost['shards']);
        }

        $xpGained = max(0, ($this->alchemyService->findAlchemySkill($character)?->xp ?? $xpBefore) - $xpBefore);
        $listingPrice = $batchCrafting->fresh()->progress['listing_price'] ?? null;

        $result = $disposition->keepsBest()
            ? $this->alchemyExperienceKeepBestService->apply($batchCrafting, $character, $item, $transmuted['slot_id'])
            : $this->alchemyBatchDispositionService->apply($character, $disposition, $item, $transmuted['slot_id'], $listingPrice);

        $progress = $batchCrafting->fresh()->progress;
        $progress['alchemy_xp_gained'] += $xpGained;
        $progress['current_item_id'] = $item->id;
        $progress['current_item_name'] = $item->affix_name ?? $item->name;
        $batchCrafting->update(['progress' => $progress]);

        return $result->withResourceSpending($cost['gold_dust'], $cost['shards']);
    }

    /**
     * Resolve the factual reason no Alchemy Experience target is currently actionable.
     *
     * @param  Skill|null  $skill  The character's already-resolved Alchemy skill, when present.
     * @return BatchCraftingEndReason SKILL_MAXED when the Alchemy skill is maxed, otherwise MAXED_OR_NOTHING_LEFT.
     */
    private function resolveNoTargetReason(?Skill $skill): BatchCraftingEndReason
    {
        if (! is_null($skill) && $this->craftingService->isSkillMaxed($skill)) {
            return BatchCraftingEndReason::SKILL_MAXED;
        }

        return BatchCraftingEndReason::MAXED_OR_NOTHING_LEFT;
    }
}
