<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\Quest;

trait CanHaveQuestItem
{
    /**
     * General method to see if the item can be given to the player.
     */
    public function canHaveItem(Character $character, Item $item): bool
    {

        if ($item->type !== 'quest') {
            return true;
        }

        $alreadyOwnsItem = InventorySlot::where('inventory_id', $character->inventory->id)
            ->where('item_id', $item->id)
            ->exists();

        if (! $alreadyOwnsItem) {
            $questThatNeedsThisItem = Quest::where('item_id', $item->id)->orWhere('secondary_required_item', $item->id)->first();

            if (! is_null($questThatNeedsThisItem)) {
                $completedQuest = $character->questsCompleted()->where('quest_id', $questThatNeedsThisItem->id)->first();

                return is_null($completedQuest);
            }

            return true;
        }

        return false;
    }

    /**
     * Used in the Adventure Rewards Combine class
     *
     * Checks if use can have the item
     */
    public static function canReceiveItem(Character $character, int $itemId): bool
    {
        $item = Item::find($itemId);

        if (! is_null($item) && $item->type !== 'quest') {
            return true;
        }

        $alreadyOwnsItem = InventorySlot::where('inventory_id', $character->inventory->id)
            ->where('item_id', $itemId)
            ->exists();

        if (! $alreadyOwnsItem) {
            $questThatNeedsThisItem = Quest::where('item_id', $itemId)->first();

            if (! is_null($questThatNeedsThisItem)) {
                $completedQuest = $character->questsCompleted()->where('quest_id', $questThatNeedsThisItem->id)->first();

                return is_null($completedQuest);
            }

            return true;
        }

        return false;
    }
}
