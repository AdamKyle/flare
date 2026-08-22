<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Market\Services\MarketBoard;
use App\Game\Shop\Events\SellItemEvent;
use App\Game\Skills\Services\DisenchantService;
use Facades\App\Game\Core\Items\Pricing\SellItemCalculator;

class HolyOilBatchDispositionService
{
    public function __construct(
        private readonly MarketBoard $marketBoard,
        private readonly DisenchantService $disenchantService,
    ) {}

    /**
     * Apply the selected disposition to a target once its planned Holy Oil work is complete.
     *
     * @param  Character  $character  The character running the batch.
     * @param  BatchCraftingDisposition  $disposition  The selected Holy Oils disposition.
     * @param  InventorySlot|SetSlot  $slot  The completed target slot.
     * @param  int|null  $listingPrice  The requested listing price, when listing.
     * @return BatchCraftingOperationResult The outcome of applying the disposition.
     */
    public function apply(Character $character, BatchCraftingDisposition $disposition, InventorySlot|SetSlot $slot, ?int $listingPrice): BatchCraftingOperationResult
    {
        return match ($disposition) {
            BatchCraftingDisposition::SELL => $this->sell($character, $slot),
            BatchCraftingDisposition::DESTROY => $this->destroy($character, $slot),
            BatchCraftingDisposition::LIST => $this->list($character, $slot, $listingPrice),
            BatchCraftingDisposition::DISENCHANT => $this->disenchant($character, $slot),
            default => BatchCraftingOperationResult::kept(0),
        };
    }

    /**
     * Sell the completed target through the real sale domain path.
     *
     * @param  Character  $character  The character running the batch.
     * @param  InventorySlot|SetSlot  $slot  The completed target slot.
     * @return BatchCraftingOperationResult The sold operation result.
     */
    private function sell(Character $character, InventorySlot|SetSlot $slot): BatchCraftingOperationResult
    {
        $goldGained = max(0, SellItemCalculator::fetchSalePriceWithAffixes($slot->item));

        if ($slot instanceof SetSlot) {
            $character->increment('gold', $goldGained);
            $slot->delete();
            event(new UpdateCharacterInventoryCountEvent($character->refresh()));

            return BatchCraftingOperationResult::sold(0, $goldGained);
        }

        event(new SellItemEvent($slot, $character));

        return BatchCraftingOperationResult::sold(0, $goldGained);
    }

    /**
     * Destroy the completed target.
     *
     * @param  Character  $character  The character running the batch.
     * @param  InventorySlot|SetSlot  $slot  The completed target slot.
     * @return BatchCraftingOperationResult The destroyed operation result.
     */
    private function destroy(Character $character, InventorySlot|SetSlot $slot): BatchCraftingOperationResult
    {
        $slot->delete();
        event(new UpdateCharacterInventoryCountEvent($character->refresh()));

        return BatchCraftingOperationResult::destroyed(0);
    }

    /**
     * List the completed target on the Market and remove it from its slot.
     *
     * @param  Character  $character  The character running the batch.
     * @param  InventorySlot|SetSlot  $slot  The completed target slot.
     * @param  int|null  $listingPrice  The requested listing price.
     * @return BatchCraftingOperationResult The listed operation result.
     */
    private function list(Character $character, InventorySlot|SetSlot $slot, ?int $listingPrice): BatchCraftingOperationResult
    {
        $this->marketBoard->listBatchCraftedItem($character, $slot->item, $listingPrice ?? 1);
        $slot->delete();
        event(new UpdateCharacterInventoryCountEvent($character->refresh()));

        return BatchCraftingOperationResult::listed(0);
    }

    /**
     * Disenchant the completed target through the real Disenchant domain path.
     *
     * @param  Character  $character  The character running the batch.
     * @param  InventorySlot|SetSlot  $slot  The completed target slot.
     * @return BatchCraftingOperationResult The disenchanted operation result.
     */
    private function disenchant(Character $character, InventorySlot|SetSlot $slot): BatchCraftingOperationResult
    {
        $this->disenchantService->setUp($character)->disenchantItem($slot, true);

        return BatchCraftingOperationResult::disenchanted(0);
    }
}
