<?php

namespace App\Game\Skills\Transformers;

use App\Flare\Models\GlobalEventCraftingInventorySlot;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use League\Fractal\TransformerAbstract;

class EventEnchantingItemTransformer extends TransformerAbstract
{
    public function __construct(private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer) {}

    public function transform(GlobalEventCraftingInventorySlot $slot): array
    {
        return [
            'slot_id' => $slot->id,
            'item_id' => $slot->item->id,
            'name' => $slot->item->affix_name ?? $slot->item->name,
            'preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
        ];
    }
}
