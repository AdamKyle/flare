<?php

namespace App\Flare\Transformers;

use App\Flare\Models\Character;

class CharacterReincarnationInfoTransformer extends BaseTransformer
{
    /**
     * Gets the response data for the character sheet
     */
    public function transform(Character $character): array
    {

        return [
            'reincarnated_times' => $character->times_reincarnated,
            'reincarnated_stat_increase' => $character->reincarnated_stat_increase,
            'xp_penalty' => $character->xp_penalty,
            'base_stat_mod' => $character->base_stat_mod,
            'base_damage_stat_mod' => $character->base_damage_stat_mod,
        ];
    }
}
