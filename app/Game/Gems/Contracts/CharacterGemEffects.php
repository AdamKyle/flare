<?php

namespace App\Game\Gems\Contracts;

use App\Game\Gems\Values\ResolvedCharacterGemEffects;

interface CharacterGemEffects
{
    /**
     * Resolve the effects supplied by character-domain Gems socketed into equipped Items.
     *
     * @param int $characterId
     * @return ResolvedCharacterGemEffects
     */
    public function resolveForCharacterId(int $characterId): ResolvedCharacterGemEffects;
}
