<?php

namespace App\Admin\Items\Transformers;

use App\Flare\Models\Item;

class ItemFormTransformer
{
    /**
     * Transform an Item into its Admin save-response / form-value representation.
     */
    public function transform(Item $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'description' => $item->description,
            'default_position' => $item->default_position,
            'market_sellable' => $item->market_sellable,
            'can_drop' => $item->can_drop,
            'cost' => $item->cost,
            'gold_dust_cost' => $item->gold_dust_cost,
            'shards_cost' => $item->shards_cost,
            'copper_coin_cost' => $item->copper_coin_cost,
            'gold_bars_cost' => $item->gold_bars_cost,
            'alchemy_type' => $item->alchemy_type,
            'specialty_type' => $item->specialty_type,
            'base_damage' => $item->base_damage,
            'base_ac' => $item->base_ac,
            'base_healing' => $item->base_healing,
            'base_damage_mod' => $item->base_damage_mod,
            'base_ac_mod' => $item->base_ac_mod,
            'base_healing_mod' => $item->base_healing_mod,
            'str_mod' => $item->str_mod,
            'dur_mod' => $item->dur_mod,
            'dex_mod' => $item->dex_mod,
            'chr_mod' => $item->chr_mod,
            'int_mod' => $item->int_mod,
            'agi_mod' => $item->agi_mod,
            'focus_mod' => $item->focus_mod,
            'ambush_chance' => $item->ambush_chance,
            'ambush_resistance' => $item->ambush_resistance,
            'counter_chance' => $item->counter_chance,
            'counter_resistance' => $item->counter_resistance,
            'effect' => $item->effect,
            'drop_location_id' => $item->drop_location_id,
            'unlocks_class_id' => $item->unlocks_class_id,
            'item_skill_id' => $item->item_skill_id,
            'skill_name' => $item->skill_name,
            'skill_bonus' => $item->skill_bonus,
            'skill_training_bonus' => $item->skill_training_bonus,
            'fight_time_out_mod_bonus' => $item->fight_time_out_mod_bonus,
            'move_time_out_mod_bonus' => $item->move_time_out_mod_bonus,
            'xp_bonus' => $item->xp_bonus,
            'ignores_caps' => $item->ignores_caps,
            'can_resurrect' => $item->can_resurrect,
            'resurrection_chance' => $item->resurrection_chance,
            'spell_evasion' => $item->spell_evasion,
            'artifact_annulment' => $item->artifact_annulment,
            'healing_reduction' => $item->healing_reduction,
            'affix_damage_reduction' => $item->affix_damage_reduction,
            'devouring_light' => $item->devouring_light,
            'devouring_darkness' => $item->devouring_darkness,
            'can_craft' => $item->can_craft,
            'craft_only' => $item->craft_only,
            'crafting_type' => $item->crafting_type,
            'skill_level_required' => $item->skill_level_required,
            'skill_level_trivial' => $item->skill_level_trivial,
            'usable' => $item->usable,
            'can_stack' => $item->can_stack,
            'lasts_for' => $item->lasts_for,
            'stat_increase' => $item->stat_increase,
            'increase_stat_by' => $item->increase_stat_by,
            'damages_kingdoms' => $item->damages_kingdoms,
            'kingdom_damage' => $item->kingdom_damage,
            'affects_skill_type' => $item->affects_skill_type,
            'increase_skill_bonus_by' => $item->increase_skill_bonus_by,
            'increase_skill_training_bonus_by' => $item->increase_skill_training_bonus_by,
            'can_use_on_other_items' => $item->can_use_on_other_items,
            'holy_level' => $item->holy_level,
            'gains_additional_level' => $item->gains_additional_level,
        ];
    }
}
