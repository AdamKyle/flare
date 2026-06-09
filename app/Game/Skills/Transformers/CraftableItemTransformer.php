<?php

namespace App\Game\Skills\Transformers;

use App\Flare\Models\Item;
use League\Fractal\TransformerAbstract;

class CraftableItemTransformer extends TransformerAbstract
{
    public function transform(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'cost' => $item->cost,
            'type' => $item->type,
            'crafting_type' => $item->crafting_type,
            'default_position' => $item->default_position,
            'skill_level_required' => $item->skill_level_required,
            'skill_level_trivial' => $item->skill_level_trivial,
        ];
    }
}
