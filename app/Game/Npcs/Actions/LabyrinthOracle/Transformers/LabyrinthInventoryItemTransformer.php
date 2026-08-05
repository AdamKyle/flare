<?php

namespace App\Game\Npcs\Actions\LabyrinthOracle\Transformers;

use App\Flare\Models\InventorySlot;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use League\Fractal\TransformerAbstract;

class LabyrinthInventoryItemTransformer extends TransformerAbstract
{
    public function __construct(private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer) {}

    public function transform(InventorySlot $slot): array
    {
        return [
            'id' => $slot->item_id,
            'affix_name' => $slot->item->affix_name,
            'preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
        ];
    }
}
