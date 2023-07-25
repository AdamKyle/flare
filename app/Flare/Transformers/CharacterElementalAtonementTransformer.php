<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;

class CharacterElementalAtonementTransformer extends BaseTransformer {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return array
     */
    public function transform(Character $character): array {

        return [
            'elemental_atonement' => $character->getInformation()->buildElementalAtonement(),
        ];
    }
}
