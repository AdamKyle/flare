<?php

namespace Tests\Setup\Gems;

use App\Game\Gems\Contracts\CharacterGemEffects;
use App\Game\Gems\Values\ResolvedCharacterGemEffects;

class FixedCharacterGemEffects implements CharacterGemEffects
{
    /**
     * @param array $amounts
     */
    public function __construct(
        private readonly array $amounts,
    ) {}

    /**
     * Return a resolved Gem effect snapshot containing the fixed modifier amounts.
     *
     * @param int $characterId
     * @return ResolvedCharacterGemEffects
     */
    public function resolveForCharacterId(int $characterId): ResolvedCharacterGemEffects
    {
        return new ResolvedCharacterGemEffects($this->amounts);
    }
}
