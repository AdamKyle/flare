<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;

class CharacterElementalAtonementTransformer extends BaseTransformer
{
    /**
     * @param CharacterCacheData $characterCacheData
     */
    public function __construct(
        private readonly CharacterCacheData $characterCacheData,
    ) {}

    /**
     * Return the Character's cached elemental atonement and dominant element, the same values battle uses.
     *
     * @param Character $character
     * @return array
     */
    public function transform(Character $character): array
    {
        $elementalAtonement = $this->characterCacheData->getCachedCharacterData($character, 'elemental_atonement');

        if (is_null($elementalAtonement)) {
            return [
                'atonements' => [
                    'fire' => 0,
                    'ice' => 0,
                    'water' => 0,
                ],
                'highest_element' => [
                    'name' => 'N/A',
                    'damage' => 0,
                ],
            ];
        }

        $atonements = $elementalAtonement['atonements'];

        return [
            'atonements' => [
                'fire' => $atonements['Fire'] ?? 0,
                'ice' => $atonements['Ice'] ?? 0,
                'water' => $atonements['Water'] ?? 0,
            ],
            'highest_element' => [
                'name' => $elementalAtonement['highest_element']['name'],
                'damage' => $elementalAtonement['highest_element']['damage'],
            ],
        ];
    }
}
