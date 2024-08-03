<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;

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
