<?php

namespace App\Game\Npcs\Actions\Seer\Transformers;

use App\Flare\Models\InventorySlot;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use League\Fractal\TransformerAbstract;

class SeerInventoryItemTransformer extends TransformerAbstract
{
    public const POSSIBLE_SOCKET_MINIMUM = 1;

    public const POSSIBLE_SOCKET_MAXIMUM = 6;

    public function __construct(private readonly CraftingItemPreviewTransformer $craftingItemPreviewTransformer) {}

    public function transform(InventorySlot $slot): array
    {
        return [
            'slot_id' => $slot->id,
            'name' => $slot->item->affix_name,
            'current_sockets' => (int) ($slot->item->sockets_count ?? $slot->item->sockets->count()),
            'possible_socket_minimum' => self::POSSIBLE_SOCKET_MINIMUM,
            'possible_socket_maximum' => self::POSSIBLE_SOCKET_MAXIMUM,
            'preview' => $this->craftingItemPreviewTransformer->transform($slot->item, $slot->id),
        ];
    }
}
