<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;

class CharacterCurrenciesTransformer extends BaseTransformer
{
    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {
        return [
            'gold' => $character->gold,
            'gold_dust' => $character->gold_dust,
            'shards' => $character->shards,
            'copper_coins' => $character->copper_coins,
        ];
    }
}
