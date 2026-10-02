<?php

namespace App\Game\Gems\Values;

use App\Flare\Models\CharacterGemModifier;

class GemAbilityOccurrence
{
    /**
     * @param CharacterGemModifier $modifier
     * @param OwnedGemItem $ownedGemItem
     */
    public function __construct(
        public readonly CharacterGemModifier $modifier,
        public readonly OwnedGemItem $ownedGemItem,
    ) {}
}
