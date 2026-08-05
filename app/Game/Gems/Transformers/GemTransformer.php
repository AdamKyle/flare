<?php

namespace App\Game\Gems\Transformers;

use App\Flare\Models\Gem;
use League\Fractal\TransformerAbstract;

class GemTransformer extends TransformerAbstract
{
    public function transform(Gem $gem): array
    {
        return [
            'id' => $gem->id,
            'name' => $gem->name,
            'tier' => $gem->tier,
            'domain' => $gem->domain,
            'primary_atonement_type' => $gem->primary_atonement_type,
            'primary_atonement_amount' => $gem->primary_atonement_amount,
            'secondary_atonement_type' => $gem->secondary_atonement_type,
            'secondary_atonement_amount' => $gem->secondary_atonement_amount,
            'tertiary_atonement_type' => $gem->tertiary_atonement_type,
            'tertiary_atonement_amount' => $gem->tertiary_atonement_amount,
            'gold_gain' => $gem->gold_gain,
            'gold_dust_gain' => $gem->gold_dust_gain,
            'shards_gain' => $gem->shards_gain,
            'copper_coin_gain' => $gem->copper_coin_gain,
            'crafting_skill_bonus' => $gem->crafting_skill_bonus,
            'item_drop_chance_increase' => $gem->item_drop_chance_increase,
            'unique_item_drop_chance_increase' => $gem->unique_item_drop_chance_increase,
            'mythic_item_drop_chance_increase' => $gem->mythic_item_drop_chance_increase,
            'cosmic_item_drop_chance_increase' => $gem->cosmic_item_drop_chance_increase,
        ];
    }
}
