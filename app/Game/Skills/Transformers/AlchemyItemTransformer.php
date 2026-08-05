<?php

namespace App\Game\Skills\Transformers;

use App\Flare\Models\Item;
use League\Fractal\TransformerAbstract;

class AlchemyItemTransformer extends TransformerAbstract
{
    public function transform(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'gold_dust_cost' => $item->gold_dust_cost,
            'shards_cost' => $item->shards_cost,
            'owned_amount' => $item->owned_amount ?? 0,
        ];
    }
}
