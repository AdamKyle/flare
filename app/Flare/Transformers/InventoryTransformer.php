<?php

namespace App\Flare\Transformers;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use League\Fractal\TransformerAbstract;

class InventoryTransformer extends TransformerAbstract
{
    /**
     * Gets the response data for the inventory sheet
     */
    public function transform(InventorySlot|SetSlot $slot): array
    {
        return [
            'item_id' => $slot->item->id,
            'slot_id' => $slot->id,
            'name' => $slot->item->affix_name,
            'description' => $slot->item->description,
            'type' => $slot->item->type,
            'affix_count' => $slot->item->affix_count,
            'is_unique' => $slot->item->is_unique,
            'is_mythic' => $slot->item->is_mythic,
            'is_cosmic' => $slot->item->is_cosmic,
            'holy_stacks_applied' => $slot->item->holy_stacks_applied,
            'max_holy_stacks' => $slot->item->holy_stacks,
            'ac' => $slot->item->getTotalDefence(),
            'attack' => $slot->item->getTotalDamage(),
            'usable' => $slot->item->usable,
            'damages_kingdoms' => $slot->item->damages_kingdoms,
            'kingdom_damage' => $slot->item->kingdom_damage,
            'lasts_for' => $slot->item->lasts_for,
            'can_stack' => $slot->item->can_stack,
            'effect' => $slot->item->effect,
            'position' => $slot->position,
        ];
    }
}
