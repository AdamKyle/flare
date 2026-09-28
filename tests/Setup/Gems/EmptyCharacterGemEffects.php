<?php

namespace Tests\Setup\Gems;

use App\Game\Gems\Contracts\CharacterGemEffects;
use App\Game\Gems\Values\ResolvedCharacterGemEffects;

class EmptyCharacterGemEffects implements CharacterGemEffects
{
    /**
     * Return an empty resolved Gem effect snapshot for tests that do not exercise Gems.
     *
     * @param int $characterId
     * @return ResolvedCharacterGemEffects
     */
    public function resolveForCharacterId(int $characterId): ResolvedCharacterGemEffects
    {
        return new ResolvedCharacterGemEffects;
    }
}
