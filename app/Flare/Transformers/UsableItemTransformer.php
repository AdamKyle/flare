<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;

class UsableItemTransformer extends TransformerAbstract {

    /**
     * Gets the response data for the inventory sheet
     *
     * @param InventorySlot|SetSlot $slot
     * @return mixed
     */
    public function transform(InventorySlot|SetSlot $slot) {
        return [
            'id'                               => $slot->item->id,
            'slot_id'                          => $slot->id,
            'item_name'                        => $slot->item->affix_name,
            'type'                             => $slot->item->type,
            'description'                      => $slot->item->description,
            'damages_kingdoms'                 => $slot->item->damages_kingdoms,
            'kingdom_damage'                   => $slot->item->kingdom_damage,
            'lasts_for'                        => $slot->item->lasts_for,
            'affects_skill_type'               => $slot->item->affects_skill_type,
            'skills'                           => GameSkill::where('type', $slot->item->affects_skill_type)->pluck('name')->toArray(),
            'increase_skill_bonus_by'          => $slot->item->increase_skill_bonus_by,
            'increase_skill_training_bonus_by' => $slot->item->increase_skill_training_bonus_by,
            'base_damage_mod_bonus'            => $slot->item->base_damage_mod_bonus,
            'base_healing_mod_bonus'           => $slot->item->base_healing_mod_bonus,
            'base_ac_mod_bonus'                => $slot->item->base_ac_mod_bonus,
            'fight_time_out_mod_bonus'         => $slot->item->fight_time_out_mod_bonus,
            'move_time_out_mod_bonus'          => $slot->item->move_time_out_mod_bonus,
            'base_damage_mod'                  => $slot->item->base_damage_mod,
            'base_ac_mod'                      => $slot->item->base_ac_mod,
            'base_healing_mod'                 => $slot->item->base_healing_mod,
            'str_mod'                          => $slot->item->str_mod,
            'dur_mod'                          => $slot->item->dur_mod,
            'int_mod'                          => $slot->item->int_mod,
            'chr_mod'                          => $slot->item->chr_mod,
            'dex_mod'                          => $slot->item->dex_mod,
            'agi_mod'                          => $slot->item->agi_mod,
            'focus_mod'                        => $slot->item->focus_mod,
            'usable'                           => $slot->item->usable,
        ];
    }
}
