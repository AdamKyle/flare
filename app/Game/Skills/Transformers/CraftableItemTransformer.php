<?php

namespace App\Game\Skills\Transformers;

use App\Flare\Models\Item;
use League\Fractal\TransformerAbstract;

class CraftableItemTransformer extends TransformerAbstract
{
    public function transform(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'cost' => $item->cost,
            'type' => $item->type,
            'crafting_type' => $item->crafting_type,
            'default_position' => $item->default_position,
            'skill_level_required' => $item->skill_level_required,
            'skill_level_trivial' => $item->skill_level_trivial,
            'description' => $item->description,
            'base_damage' => $item->base_damage ?? 0,
            'base_damage_mod' => $item->base_damage_mod,
            'base_ac' => $item->base_ac ?? 0,
            'base_ac_mod' => $item->base_ac_mod,
            'base_healing' => $item->base_healing ?? 0,
            'base_healing_mod' => $item->base_healing_mod,
            'str_modifier' => $item->str_mod,
            'dur_modifier' => $item->dur_mod,
            'dex_modifier' => $item->dex_mod,
            'chr_modifier' => $item->chr_mod,
            'int_modifier' => $item->int_mod,
            'agi_modifier' => $item->agi_mod,
            'focus_modifier' => $item->focus_mod,
            'ambush_chance' => $item->ambush_chance,
            'ambush_resistance_chance' => $item->ambush_resistance,
            'counter_chance' => $item->counter_chance,
            'counter_resistance_chance' => $item->counter_resistance,
            'is_mythic' => $item->is_mythic,
            'is_cosmic' => $item->is_cosmic,
            'is_unique' => $item->is_unique,
            'affix_count' => $item->affix_count,
            'holy_stacks_applied' => $item->holy_stacks_applied,
            'holy_stack_stat_bonus' => $item->holy_stack_stat_bonus,
            'holy_stack_devouring_darkness' => $item->holy_stack_devouring_darkness,
            'usable' => $item->usable,
            'holy_level' => $item->holy_level,
            'damages_kingdoms' => $item->damages_kingdoms,
            'item_prefix' => $item->itemPrefix,
            'item_suffix' => $item->itemSuffix,
        ];
    }
}
