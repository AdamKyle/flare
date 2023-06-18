<?php

namespace App\Flare\Transformers;

use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use League\Fractal\TransformerAbstract;

class InventoryTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the inventory sheet
     *
     * @param InventorySlot|SetSlot $slot
     * @return array
     */
    public function transform(InventorySlot|SetSlot $slot): array {
        return [
            'id'                      => $slot->id,
            'item_id'                 => $slot->item->id,
            'slot_id'                 => $slot->id,
            'item_name'               => $slot->item->affix_name,
            'type'                    => $slot->item->type,
            'description'             => $slot->item->description,
            'attached_affixes_count'  => $slot->item->affix_count,
            'is_unique'               => $slot->item->is_unique,
            'is_mythic'               => $slot->item->is_mythic,
            'has_holy_stacks_applied' => $slot->item->holy_stacks_applied,
            'ac'                      => $slot->item->getTotalDefence(),
            'attack'                  => $slot->item->getTotalDamage(),
            'usable'                  => $slot->item->usable,
            'holy_stacks'             => $slot->item->holy_stacks,
            'position'                => ucfirst(str_replace('-', ' ', $slot->position)),
            'item_skills'             => $slot->item->itemSkill()->with('children')->get(),
            'item_skill_progressions' => $slot->item->itemSkillProgressions,
        ];
    }
}
