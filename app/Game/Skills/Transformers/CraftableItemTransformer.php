<?php

namespace App\Game\Skills\Transformers;

use App\Flare\Models\Item;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use League\Fractal\TransformerAbstract;

class CraftableItemTransformer extends TransformerAbstract
{
    public function __construct(private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer) {}

    public function transform(Item $item): array
    {
        return [
            'id' => $item->id,
            'cost' => $item->cost,
            'crafting_type' => $item->crafting_type,
            'default_position' => $item->default_position,
            'skill_level_required' => $item->skill_level_required,
            'skill_level_trivial' => $item->skill_level_trivial,
            'preview' => $this->craftingItemPreviewTransformer->transform($item),
        ];
    }
}
