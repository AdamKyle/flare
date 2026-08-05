<?php

namespace App\Game\Npcs\Actions\QueenOfHearts\Transformers;

use App\Flare\Models\InventorySlot;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use League\Fractal\TransformerAbstract;

class QueenInventorySlotTransformer extends TransformerAbstract
{
    public function __construct(private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer) {}

    public function transform(InventorySlot $slot): array
    {
        return [
            'slot_id' => $slot->id,
            'item_id' => $slot->item->id,
            'preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
        ];
    }
}
