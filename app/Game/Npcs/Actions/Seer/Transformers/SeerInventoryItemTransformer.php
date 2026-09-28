<?php

namespace App\Game\Npcs\Actions\Seer\Transformers;

use App\Flare\Models\InventorySlot;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Core\Items\Values\ItemSocketEligibility;
use League\Fractal\TransformerAbstract;

class SeerInventoryItemTransformer extends TransformerAbstract
{
    /**
     * @param CraftingItemPreviewTransformer $craftingItemPreviewTransformer
     * @param ItemSocketEligibility $itemSocketEligibility
     */
    public function __construct(
        private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer,
        private readonly ItemSocketEligibility $itemSocketEligibility,
    ) {}

    /**
     * Transform an eligible inventory slot into factual Seer socket data.
     *
     * @param InventorySlot $slot
     * @return array
     */
    public function transform(InventorySlot $slot): array
    {
        return [
            'slot_id' => $slot->id,
            'name' => $slot->item->affix_name,
            'current_sockets' => $slot->item->socket_count,
            'max_socket_count' => $this->itemSocketEligibility->maxSocketCount($slot->item->type),
            'possible_socket_minimum' => $this->minimumResult($slot),
            'possible_socket_maximum' => $this->itemSocketEligibility->maxSocketCount($slot->item->type),
            'preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
        ];
    }

    /**
     * Return the lowest possible non-decreasing Seer result for this Item.
     *
     * @param InventorySlot $slot
     * @return int
     */
    private function minimumResult(InventorySlot $slot): int
    {
        if ($this->itemSocketEligibility->isTwoHanded($slot->item->type) && $slot->item->socket_count <= 0) {
            return 3;
        }

        return max($slot->item->socket_count, 1);
    }
}
