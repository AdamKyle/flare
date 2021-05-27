<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\Item;

class ItemTransfromer extends TransformerAbstract {

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Item $item) {

        return [
            'id'                   => $item->id,
            'base_damage'          => $item->getTotalDamage(),
            'base_ac'              => $item->getTotalDefence(),
            'base_healing'         => $item->getTotalHealing(),
            'str_modifier'         => $item->getTotalPercentageForStat('str'),
            'dur_modifier'         => $item->getTotalPercentageForStat('dur'),
            'int_modifier'         => $item->getTotalPercentageForStat('int'),
            'dex_modifier'         => $item->getTotalPercentageForStat('dex'),
            'chr_modifier'         => $item->getTotalPercentageForStat('chr'),
            'type'                 => $item->type,
            'skill_name'           => $item->skill_training_name,
            'skill_training_bonus' => $item->skill_training_bonus,
            'skill_bonus'          => $item->skill_bonus,
            'item_prefix'          => $item->itemPrefix,
            'item_suffix'          => $item->itemSuffix,
            'crafting_type'        => $item->crafting_type,
            'skill_level_req'      => $item->skill_level_required,
            'skill_level_trivial'  => $item->skill_level_trivial,
        ];
    }
}
