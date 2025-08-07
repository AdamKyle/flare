<?php

namespace App\Flare\Items\Transformers;

use App\Flare\Models\Item;
use League\Fractal\TransformerAbstract;

class UsableItemTransformer extends TransformerAbstract
{
    public function transform(Item $item): array
    {
        return [
            'id'                                 => $item->id,
            'name'                               => $item->name,
            'description'                        => nl2br(e($item->description)),
            'damages_kingdoms'                   => $item->damages_kingdoms,
            'kingdom_damage'                     => $item->kingdom_damage,
            'lasts_for'                          => $item->lasts_for,
            'stat_increase'                      => $item->stat_increase,
            'increase_stat_by'                   => $item->increase_stat_by,
            'increase_skill_bonus_by'            => $item->increase_skill_bonus_by,
            'increase_skill_training_bonus_by'   => $item->increase_skill_training_bonus_by,
            'affects_skills'                     => $item->affects_skill_type,
            'gold_bars_cost'                     => $item->gold_bars_cost,
            'shards_cost'                        => $item->shards_cost,
            'gold_dust_cost'                     => $item->gold_dust_cost,
            'copper_coin_cost'                   => $item->copper_coin_cost,
        ];
    }
}
