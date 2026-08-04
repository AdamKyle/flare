<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Items\Values\ItemEffectType;

trait CharacterMaxLevel
{
    /**
     * Get the characters max level.
     */
    public function getMaxLevel(Character $character): int
    {

        $hasQuestItem = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectType::CONTINUE_LEVELING->value;
        })->isNotEmpty();

        if ($hasQuestItem) {
            return MaxLevelConfiguration::first()->max_level;
        }

        return MaxLevel::MAX_LEVEL;
    }
}
