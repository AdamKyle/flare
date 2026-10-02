<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class CharacterElementalPenetrationTransformer extends BaseTransformer
{
    /**
     * @param CharacterCacheData $characterCacheData
     */
    public function __construct(
        private readonly CharacterCacheData $characterCacheData,
    ) {}

    /**
     * Return the Character's cached Fire, Ice, and Water elemental penetration, the same values battle uses.
     *
     * @param Character $character
     * @return array
     */
    public function transform(Character $character): array
    {
        $elementalPenetration = $this->characterCacheData->getCachedCharacterData($character, 'elemental_penetration') ?? [];

        return [
            'fire' => $elementalPenetration['Fire'] ?? 0.0,
            'ice' => $elementalPenetration['Ice'] ?? 0.0,
            'water' => $elementalPenetration['Water'] ?? 0.0,
        ];
    }
}
