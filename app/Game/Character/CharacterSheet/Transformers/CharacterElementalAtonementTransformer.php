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

        return [
            'elemental_atonement' => $character->getInformation()->buildElementalAtonement(),
        ];
    }
}
