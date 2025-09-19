<?php

namespace App\Flare\Transformers;

use App\Flare\Models\GameSkill;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\SetSlot;
use League\Fractal\TransformerAbstract;

class UsableItemTransformer extends TransformerAbstract
{
    /**
     * Gets the response data for the inventory sheet
     */
    public function transform(InventorySlot|SetSlot|Item $slot): array
    {
        return [
            'id' => $slot->id,
            'item_id' => $slot->item_id,
            'slot_id' => $slot->id,
            'name' => $slot->item->affix_name,
            'type' => $slot->item->type,
            'description' => $slot->item->description,
            'damages_kingdoms' => $slot->item->damages_kingdoms,
            'kingdom_damage' => $slot->item->kingdom_damage,
            'lasts_for' => $slot->item->lasts_for,
            'affects_skill_type' => $slot->item->affects_skill_type,
            'skills' => GameSkill::where('type', $slot->item->affects_skill_type)->pluck('name')->toArray(),
            'increase_skill_bonus_by' => $slot->item->increase_skill_bonus_by,
            'increase_skill_training_bonus_by' => $slot->item->increase_skill_training_bonus_by,
            'fight_time_out_mod_bonus' => $slot->item->fight_time_out_mod_bonus,
            'move_time_out_mod_bonus' => $slot->item->move_time_out_mod_bonus,
            'base_damage_mod' => $slot->item->base_damage_mod,
            'base_ac_mod' => $slot->item->base_ac_mod,
            'base_healing_mod' => $slot->item->base_healing_mod,
            'usable' => $slot->item->usable,
            'stat_increase' => $slot->item->increase_stat_by,
            'holy_level' => $slot->item->holy_level,
            'can_stack' => $slot->item->can_stack,
            'gain_additional_level' => $slot->item->gains_additional_level,
            'xp_bonus' => $slot->item->xp_bonus,
        ];
    }
}
