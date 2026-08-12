<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;

class CharacterElementalAtonementTransformer extends BaseTransformer
{
    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {
        $elementalAtonement = $character->getInformation()->buildElementalAtonement();

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

        $elementalAtonement['atonements'] = [
            'fire' => $atonements['fire'] ?? 0,
            'ice' => $atonements['ice'] ?? 0,
            'water' => $atonements['water'] ?? 0,
        ];

        return $elementalAtonement;
    }
}
