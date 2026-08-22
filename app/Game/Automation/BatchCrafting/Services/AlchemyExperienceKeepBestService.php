<?php

namespace App\Game\Automation\BatchCrafting\Services;

use App\Flare\Models\BatchCrafting;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Automation\BatchCrafting\Values\BatchCraftingOperationResult;
use App\Game\Character\CharacterInventory\Services\CharacterInventoryService;

class AlchemyExperienceKeepBestService
{
    public function __construct(private readonly CharacterInventoryService $characterInventoryService) {}

    /**
     * Apply the Keep Best Alchemy comparison to a newly transmuted item.
     *
     * Retains only the single highest-requirement item produced so far in the character's
     * Alchemy Bag, destroying whichever result (the new one, or the previously retained one)
     * is no longer the strongest.
     *
     * @param  BatchCrafting  $batchCrafting  The running Batch Crafting record, whose progress is updated and persisted.
     * @param  Character  $character  The character running the batch.
     * @param  Item  $item  The item just successfully transmuted.
     * @param  int  $alchemyBagSlotId  The Alchemy Bag slot holding the newly produced item.
     * @return BatchCraftingOperationResult The outcome of applying the Keep Best comparison.
     */
    public function apply(BatchCrafting $batchCrafting, Character $character, Item $item, int $alchemyBagSlotId): BatchCraftingOperationResult
    {
        $progress = $batchCrafting->fresh()->progress;
        $currentBest = $progress['alchemy_kept_best'] ?? null;
        $newQuality = $item->skill_level_required;

        if (! is_null($currentBest) && $currentBest['slot_id'] === $alchemyBagSlotId) {
            return BatchCraftingOperationResult::kept(0);
        }

        if (! is_null($currentBest) && $currentBest['quality'] > $newQuality) {
            $this->destroy($character, $alchemyBagSlotId);

            return BatchCraftingOperationResult::destroyed(0);
        }

        if (! is_null($currentBest)) {
            $this->destroy($character, $currentBest['slot_id']);
        }

        $progress['alchemy_kept_best'] = ['slot_id' => $alchemyBagSlotId, 'item_id' => $item->id, 'quality' => $newQuality];
        $batchCrafting->update(['progress' => $progress]);

        return BatchCraftingOperationResult::kept(0);
    }

    /**
     * Destroy a displaced Alchemy Bag slot.
     *
     * @param  Character  $character  The character running the batch.
     * @param  int  $alchemyBagSlotId  The Alchemy Bag slot being destroyed.
     * @return void This method does not return a value.
     */
    private function destroy(Character $character, int $alchemyBagSlotId): void
    {
        $this->characterInventoryService->setCharacter($character)->destroyAlchemyItem($alchemyBagSlotId);
    }
}
