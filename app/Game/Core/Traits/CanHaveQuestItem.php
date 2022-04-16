<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\Quest;

Trait CanHaveQuestItem {

    /**
     * General method to see if the item can be given to the player.
     *
     * @param Character $character
     * @param Item $item
     * @return bool
     */
    public function canHaveItem(Character $character, Item $item): bool {

        if ($item->type !== 'quest') {
            return true;
        }

        $foundItem = $character->inventory->slots->filter(function($slot) use($item) {
            return $slot->item_id === $item->id && $slot->item->type === 'quest';
        })->first();

        if (is_null($foundItem)) {
            $questThatNeedsThisItem = Quest::where('item_id', $item->id)->orWhere('secondary_required_item', $item->id)->first();

            if (!is_null($questThatNeedsThisItem)) {
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
     *
     * @param Character $character
     * @param int $itemId
     * @return bool
     */
    public static function canReceiveItem(Character $character, int $itemId): bool {
        $foundItem = $character->inventory->slots->filter(function($slot) use($itemId) {
            return $slot->item_id === $itemId && $slot->item->type === 'quest';
        })->first();

        if (is_null($foundItem)) {
            $questThatNeedsThisItem = Quest::where('item_id', $itemId)->first();

            if (!is_null($questThatNeedsThisItem)) {
                $completedQuest = $character->questsCompleted()->where('quest_id', $questThatNeedsThisItem->id)->first();

                return is_null($completedQuest);
            }

            return true;
        }

        return false;
    }
}
