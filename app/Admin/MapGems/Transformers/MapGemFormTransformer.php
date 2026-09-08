<?php

namespace App\Admin\MapGems\Transformers;

use App\Flare\Models\GameMapGemParamter;

class MapGemFormTransformer
{
    /**
     * Transform a Map Gem profile into its Admin save-response / form-value representation.
     */
    public function transform(GameMapGemParamter $gameMapGemParamter): array
    {
        return [
            'id' => $gameMapGemParamter->id,
            'game_map_id' => $gameMapGemParamter->game_map_id,
            'name' => $gameMapGemParamter->name,
            'description' => $gameMapGemParamter->description,
            'character_xp_bonus_range' => $gameMapGemParamter->character_xp_bonus_range,
            'character_class_rank_xp_bonus_range' => $gameMapGemParamter->character_class_rank_xp_bonus_range,
            'kingdom_passive_training_reduction_range' => $gameMapGemParamter->kingdom_passive_training_reduction_range,
            'character_class_specialty_xp_gain_range' => $gameMapGemParamter->character_class_specialty_xp_gain_range,
            'crafting_skill_ids' => $gameMapGemParamter->crafting_skill_ids ?? [],
            'crafting_skill_bonus_range' => $gameMapGemParamter->crafting_skill_bonus_range,
            'gold_gain_range' => $gameMapGemParamter->gold_gain_range,
            'gold_dust_gain_range' => $gameMapGemParamter->gold_dust_gain_range,
            'shards_gain_range' => $gameMapGemParamter->shards_gain_range,
            'copper_coin_gain_range' => $gameMapGemParamter->copper_coin_gain_range,
            'item_drop_chance_increase_range' => $gameMapGemParamter->item_drop_chance_increase_range,
            'unique_item_drop_chance_increase_range' => $gameMapGemParamter->unique_item_drop_chance_increase_range,
            'mythic_item_drop_chance_increase_range' => $gameMapGemParamter->mythic_item_drop_chance_increase_range,
            'cosmic_item_drop_chance_increase_range' => $gameMapGemParamter->cosmic_item_drop_chance_increase_range,
            'character_power_reduction_range' => $gameMapGemParamter->character_power_reduction_range,
            'enemy_strength_increase_range' => $gameMapGemParamter->enemy_strength_increase_range,
            'enemy_healing_increase_range' => $gameMapGemParamter->enemy_healing_increase_range,
            'enemy_spell_evasion_range' => $gameMapGemParamter->enemy_spell_evasion_range,
            'enemy_affix_resistance_range' => $gameMapGemParamter->enemy_affix_resistance_range,
            'enemy_entrancing_chance_range' => $gameMapGemParamter->enemy_entrancing_chance_range,
            'enemy_devouring_light_chance_range' => $gameMapGemParamter->enemy_devouring_light_chance_range,
            'enemy_devouring_darkness_chance_range' => $gameMapGemParamter->enemy_devouring_darkness_chance_range,
            'enemy_ambush_chance_range' => $gameMapGemParamter->enemy_ambush_chance_range,
            'enemy_ambush_resistance_range' => $gameMapGemParamter->enemy_ambush_resistance_range,
            'enemy_counter_chance_range' => $gameMapGemParamter->enemy_counter_chance_range,
            'enemy_counter_resistance_range' => $gameMapGemParamter->enemy_counter_resistance_range,
            'enemy_quest_item_drop_chance_increase_range' => $gameMapGemParamter->enemy_quest_item_drop_chance_increase_range,
            'monster_xp_increase_range' => $gameMapGemParamter->monster_xp_increase_range,
            'monster_gold_drop_increase_range' => $gameMapGemParamter->monster_gold_drop_increase_range,
            'monster_atonement' => $gameMapGemParamter->monster_atonement,
            'monster_atonement_range' => $gameMapGemParamter->monster_atonement_range,
        ];
    }
}
