<?php

namespace App\Game\Gems\Progression\Contracts;

use App\Game\Gems\Values\ResolvedAreaGemEffects;

interface CharacterAreaGemEffects
{
    /**
     * Resolve the Character-aware Gem effects for the given Character's
     * current Map/Location context, adjusted by global/personal Gem progression.
     *
     * @param int $characterId
     * @return ResolvedAreaGemEffects
     */
    public function resolveForCharacterId(int $characterId): ResolvedAreaGemEffects;
}
