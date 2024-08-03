<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;

trait CharacterMaxLevel
{
    /**
     * Get the characters max level.
     */
    public function getMaxLevel(Character $character): int
    {

        $hasQuestItem = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::CONTINUE_LEVELING;
        })->isNotEmpty();

        if ($hasQuestItem) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return MaxLevel::MAX_LEVEL;
    }
}
