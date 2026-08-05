<?php

namespace App\Game\Skills\Transformers;

use App\Flare\Models\ItemAffix;
use League\Fractal\TransformerAbstract;

class EnchantingAffixTransformer extends TransformerAbstract
{
    public function transform(ItemAffix $affix): array
    {
        return [
            'id' => $affix->id,
            'name' => $affix->name,
            'type' => $affix->type,
            'int_required' => $affix->int_required,
            'cost' => $affix->cost,
            'skill_level_required' => $affix->skill_level_required,
        ];
    }
}
