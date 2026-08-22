<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\AlchemyBagSlot;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Enums\BatchCraftingDisposition;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;
use App\Game\Character\CharacterInventory\Services\UseItemService;
use App\Game\Market\Services\MarketBoard;

class AlchemyBatchDispositionService
{
    public function __construct(
        private readonly CharacterInventoryService $characterInventoryService,
        private readonly UseItemService $useItemService,
        private readonly MarketBoard $marketBoard,
    ) {}

    /**
     * Apply the selected disposition to one successfully transmuted Alchemy item.
     *
     * @param  Character  $character  The character running the batch.
     * @param  BatchCraftingDisposition  $disposition  The selected Alchemy disposition.
     * @param  Item  $item  The transmuted item.
     * @param  int  $alchemyBagSlotId  The Alchemy Bag slot holding the produced item.
     * @param  int|null  $listingPrice  The requested listing price, when listing.
     * @return BatchCraftingOperationResult The outcome of applying the disposition.
     */
    public function apply(Character $character, BatchCraftingDisposition $disposition, Item $item, int $alchemyBagSlotId, ?int $listingPrice): BatchCraftingOperationResult
    {
        return match ($disposition) {
            BatchCraftingDisposition::DESTROY => $this->destroy($character, $alchemyBagSlotId),
            BatchCraftingDisposition::LIST => $this->list($character, $item, $alchemyBagSlotId, $listingPrice),
            BatchCraftingDisposition::USE_NOW => $this->useNow($character, $alchemyBagSlotId),
            default => BatchCraftingOperationResult::kept(0),
        };
    }

    /**
     * Destroy the produced item's Alchemy Bag slot.
     *
     * @param  Character  $character  The character running the batch.
     * @param  int  $alchemyBagSlotId  The Alchemy Bag slot holding the produced item.
     * @return BatchCraftingOperationResult The destroyed operation result.
     */
    private function destroy(Character $character, int $alchemyBagSlotId): BatchCraftingOperationResult
    {
        $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem($alchemyBagSlotId);

        return BatchCraftingOperationResult::destroyed(0);
    }

    /**
     * List the produced item on the Market and remove it from the Alchemy Bag.
     *
     * @param  Character  $character  The character running the batch.
     * @param  Item  $item  The transmuted item.
     * @param  int  $alchemyBagSlotId  The Alchemy Bag slot holding the produced item.
     * @param  int|null  $listingPrice  The requested listing price.
     * @return BatchCraftingOperationResult The listed operation result.
     */
    private function list(Character $character, Item $item, int $alchemyBagSlotId, ?int $listingPrice): BatchCraftingOperationResult
    {
        $this->marketBoard->listBatchCraftedItem($character, $item, $listingPrice ?? 1);
        $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem($alchemyBagSlotId);

        return BatchCraftingOperationResult::listed(0);
    }

    /**
     * Use the produced item immediately through the real Alchemy item use domain path.
     *
     * When the domain rule prevents immediate use, the item remains retained in the Alchemy Bag.
     *
     * @param  Character  $character  The character running the batch.
     * @param  int  $alchemyBagSlotId  The Alchemy Bag slot holding the produced item.
     * @return BatchCraftingOperationResult The used operation result, or a kept result when use was blocked.
     */
    private function useNow(Character $character, int $alchemyBagSlotId): BatchCraftingOperationResult
    {
        $slot = AlchemyBagSlot::where('id', $alchemyBagSlotId)->where('character_id', $character->id)->first();

        if (is_null($slot)) {
            return BatchCraftingOperationResult::kept(0);
        }

        $result = $this->useItemService->useSingleAlchemyItem($character, $slot);

        if (($result['status'] ?? null) !== 200) {
            return BatchCraftingOperationResult::kept(0);
        }

        return BatchCraftingOperationResult::used();
    }
}
