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

        $item = $slot;
        $slotId = null;

        if (! ($slot instanceof Item)) {
            $item = $slot->item;
            $slotId = $slot->id;
        }

        return [
            'item_id' => $item->id,
            'slot_id' => $slotId,
            'name' => $item->affix_name,
            'type' => $item->type,
            'description' => $item->description,
            'damages_kingdoms' => $item->damages_kingdoms,
            'kingdom_damage' => $item->kingdom_damage,
            'lasts_for' => $item->lasts_for,
            'affects_skill_type' => $item->affects_skill_type,
            'skills' => GameSkill::where('type', $item->affects_skill_type)->pluck('name')->toArray(),
            'increase_skill_bonus_by' => $item->increase_skill_bonus_by,
            'increase_skill_training_bonus_by' => $item->increase_skill_training_bonus_by,
            'fight_time_out_mod_bonus' => $item->fight_time_out_mod_bonus,
            'move_time_out_mod_bonus' => $item->move_time_out_mod_bonus,
            'base_damage_mod' => $item->base_damage_mod,
            'base_ac_mod' => $item->base_ac_mod,
            'base_healing_mod' => $item->base_healing_mod,
            'usable' => $item->usable,
            'stat_increase' => $item->increase_stat_by,
            'holy_level' => $item->holy_level,
            'can_stack' => $item->can_stack,
            'gain_additional_level' => $item->gains_additional_level,
            'xp_bonus' => $item->xp_bonus,
            'gold_dust_cost' => $item->gold_dust_cost,
            'shards_cost' => $item->shards_cost,
            'gold_bars_cost' => $item->gold_bars_cost,
        ];
    }
}
