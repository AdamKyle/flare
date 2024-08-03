<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;

class CharacterCurrenciesTransformer extends BaseTransformer
{
    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {
        return [
            'gold' => number_format($character->gold),
            'gold_dust' => number_format($character->gold_dust),
            'shards' => number_format($character->shards),
            'copper_coins' => number_format($character->copper_coins),
        ];
    }
}
