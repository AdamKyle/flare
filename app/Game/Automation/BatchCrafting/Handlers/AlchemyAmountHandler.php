<?php

namespace App\Game\Automation\BatchCrafting\Handlers;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Attributes\HandlesBatchCraftingMode;
use App\Game\Automation\BatchCrafting\Contracts\BatchCraftingHandler;
use App\Game\Automation\BatchCrafting\Enums\AlchemyBatchMode;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingEndReason;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingType;
use App\Game\Automation\BatchCrafting\Services\AlchemyBatchDispositionService;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Skills\Services\AlchemyService;

#[HandlesBatchCraftingMode(BatchCraftingType::ALCHEMY, AlchemyBatchMode::AMOUNT)]
class AlchemyAmountHandler implements BatchCraftingHandler
{
    public function __construct(
        private readonly AlchemyService $alchemyService,
        private readonly AlchemyBatchDispositionService $alchemyBatchDispositionService,
    ) {}

    /**
     * Execute one Alchemy Amount Batch Crafting operation for the running batch.
     *
     * @param BatchCrafting $batchCrafting The running Batch Crafting record.
     * @param Character $character The character running the batch.
     * @return BatchCraftingOperationResult The outcome of the operation.
     */
    public function handle(BatchCrafting $batchCrafting, Character $character): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->progress;

        if ($progress['completed_amount'] >= $progress['alchemy_amount']) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        $item = $this->findAlchemyItem($progress['alchemy_item_id']);

        if (is_null($item)) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_ALCHEMY_ITEMS);
        }

        $disposition = BatchCraftingDisposition::from($batchCrafting->disposition);
        $cost = $this->alchemyService->resolveCost($character, $item);

        if ($cost['gold_dust'] > $character->gold_dust) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_GOLD_DUST);
        }

        if ($cost['shards'] > $character->shards) {
            return BatchCraftingOperationResult::ended(BatchCraftingEndReason::NO_SHARDS);
        }

        $skill = $this->alchemyService->findAlchemySkill($character);
        $xpBefore = $skill?->xp ?? 0;
        $bypassBagCapacity = $disposition !== BatchCraftingDisposition::KEEP;

        $transmuted = $this->alchemyService->transmute($character, $item->id, true, $bypassBagCapacity);

        if (is_null($transmuted)) {
            return BatchCraftingOperationResult::failed(0)->withResourceSpending($cost['gold_dust'], $cost['shards']);
        }

        $xpGained = max(0, ($this->alchemyService->findAlchemySkill($character)?->xp ?? $xpBefore) - $xpBefore);
        $listingPrice = $progress['listing_price'] ?? null;

        $result = $this->alchemyBatchDispositionService->apply($character, $disposition, $item, $transmuted['slot_id'], $listingPrice);

        $progress = $batchCrafting->fresh()->progress;
        $progress['completed_amount']++;
        $progress['alchemy_xp_gained'] += $xpGained;
        $progress['current_item_id'] = $item->id;
        $progress['current_item_name'] = $item->affix_name ?? $item->name;
        $batchCrafting->update(['progress' => $progress]);

        $result = $result->withResourceSpending($cost['gold_dust'], $cost['shards']);

        if ($progress['completed_amount'] >= $progress['alchemy_amount']) {
            return $result->withEndReason(BatchCraftingEndReason::AMOUNT_REACHED);
        }

        return $result;
    }

    /**
     * Resolve the requested Alchemy item, when it is still a valid craftable Alchemy item.
     *
     * @param int $itemId The requested Alchemy item id.
     * @return Item|null The resolved item, or null when it is no longer valid.
     */
    private function findAlchemyItem(int $itemId): ?Item
    {
        return Item::where('id', $itemId)
            ->where('can_craft', true)
            ->where('crafting_type', 'alchemy')
            ->first();
    }
}
