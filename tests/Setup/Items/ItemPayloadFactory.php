<?php

namespace Tests\Setup\Items;

class ItemPayloadFactory
{
    /**
     * Build a valid, minimal Admin Item creation/update request payload.
     *
     * @param array<string, mixed> $overrides Field overrides.
     * @return array<string, mixed> Complete Item form payload.
     */
    public function valid(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Item',
            'type' => 'weapon',
            'description' => 'A test item.',
            'default_position' => null,
            'market_sellable' => true,
            'can_drop' => true,
            'cost' => 100,
            'gold_dust_cost' => null,
            'shards_cost' => null,
            'copper_coin_cost' => null,
            'gold_bars_cost' => null,
            'alchemy_type' => null,
            'specialty_type' => null,
            'base_damage' => 10,
            'base_ac' => null,
            'base_healing' => null,
            'base_damage_mod' => null,
            'base_ac_mod' => null,
            'base_healing_mod' => null,
            'str_mod' => null,
            'dur_mod' => null,
            'dex_mod' => null,
            'chr_mod' => null,
            'int_mod' => null,
            'agi_mod' => null,
            'focus_mod' => null,
            'ambush_chance' => null,
            'ambush_resistance' => null,
            'counter_chance' => null,
            'counter_resistance' => null,
            'effect' => null,
            'drop_location_id' => null,
            'unlocks_class_id' => null,
            'item_skill_id' => null,
            'skill_name' => null,
            'skill_bonus' => null,
            'skill_training_bonus' => null,
            'fight_time_out_mod_bonus' => null,
            'move_time_out_mod_bonus' => null,
            'xp_bonus' => null,
            'ignores_caps' => false,
            'can_resurrect' => false,
            'resurrection_chance' => null,
            'spell_evasion' => null,
            'artifact_annulment' => null,
            'healing_reduction' => null,
            'affix_damage_reduction' => null,
            'devouring_light' => null,
            'devouring_darkness' => null,
            'can_craft' => false,
            'craft_only' => false,
            'crafting_type' => null,
            'skill_level_required' => null,
            'skill_level_trivial' => null,
            'usable' => false,
            'can_stack' => false,
            'lasts_for' => null,
            'stat_increase' => false,
            'increase_stat_by' => null,
            'damages_kingdoms' => false,
            'kingdom_damage' => null,
            'affects_skill_type' => null,
            'increase_skill_bonus_by' => null,
            'increase_skill_training_bonus_by' => null,
            'can_use_on_other_items' => false,
            'holy_level' => null,
            'gains_additional_level' => false,
        ], $overrides);
    }
}
