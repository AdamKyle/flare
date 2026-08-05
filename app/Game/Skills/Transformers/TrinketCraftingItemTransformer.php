<?php

namespace App\Game\Skills\Transformers;

use App\Flare\Models\Item;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use League\Fractal\TransformerAbstract;

class TrinketCraftingItemTransformer extends TransformerAbstract
{
    public function __construct(private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer) {}

    public function transform(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'gold_dust_cost' => $item->gold_dust_cost,
            'copper_coin_cost' => $item->copper_coin_cost,
            'skill_level_required' => $item->skill_level_required,
            'preview' => $this->craftingItemPreviewTransformer->transform($item),
        ];
    }
}
