<?php

namespace App\Core\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\Item;

Trait CanHaveQuestItem {

    public function canHaveItem(Character $character, Item $item): bool {
        $doesntHave = $character->inventory->slots->filter(function ($slot) use ($item) {
            return $slot->item_id === $item->id && $item->type === 'quest';
        })->isEmpty();

        $hasCompletedQuest = $character->questsCompleted->filter(function($questCompleted) use ($item) {
            return $questCompleted->quest->item_id === $item->id;
        })->isEmpty();

        return $doesntHave && $hasCompletedQuest;
    }
}