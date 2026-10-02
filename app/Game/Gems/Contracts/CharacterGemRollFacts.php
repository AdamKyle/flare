<?php

namespace App\Game\Gems\Contracts;

interface CharacterGemRollFacts
{
    /**
     * Return the canonical non-ability modifier roll ranges for the Gem tier.
     *
     * @param int $tier
     * @return array
     */
    public function possibleModifierRanges(int $tier): array;

    /**
     * Return the Gem Abilities that can roll for the Gem tier.
     *
     * @param int $tier
     * @return array
     */
    public function possibleAbilities(int $tier): array;
}
