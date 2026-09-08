<?php

namespace App\Admin\LocationGems\Transformers;

use App\Flare\Models\GameLocationGemParamter;

class LocationGemFormTransformer
{
    /**
     * Transform a Location Gem profile into its Admin save-response / form-value representation.
     */
    public function transform(GameLocationGemParamter $gameLocationGemParamter): array
    {
        return [
            'id' => $gameLocationGemParamter->id,
            'location_id' => $gameLocationGemParamter->location_id,
            'name' => $gameLocationGemParamter->name,
            'description' => $gameLocationGemParamter->description,
            'character_xp_bonus_range' => $gameLocationGemParamter->character_xp_bonus_range,
            'character_class_rank_xp_bonus_range' => $gameLocationGemParamter->character_class_rank_xp_bonus_range,
            'kingdom_passive_training_reduction_range' => $gameLocationGemParamter->kingdom_passive_training_reduction_range,
            'character_class_specialty_xp_gain_range' => $gameLocationGemParamter->character_class_specialty_xp_gain_range,
            'crafting_skill_ids' => $gameLocationGemParamter->crafting_skill_ids ?? [],
            'crafting_skill_bonus_range' => $gameLocationGemParamter->crafting_skill_bonus_range,
            'gold_gain_range' => $gameLocationGemParamter->gold_gain_range,
            'gold_dust_gain_range' => $gameLocationGemParamter->gold_dust_gain_range,
            'shards_gain_range' => $gameLocationGemParamter->shards_gain_range,
            'copper_coin_gain_range' => $gameLocationGemParamter->copper_coin_gain_range,
            'item_drop_chance_increase_range' => $gameLocationGemParamter->item_drop_chance_increase_range,
            'unique_item_drop_chance_increase_range' => $gameLocationGemParamter->unique_item_drop_chance_increase_range,
            'mythic_item_drop_chance_increase_range' => $gameLocationGemParamter->mythic_item_drop_chance_increase_range,
            'cosmic_item_drop_chance_increase_range' => $gameLocationGemParamter->cosmic_item_drop_chance_increase_range,
            'enemy_strength_increase_range' => $gameLocationGemParamter->enemy_strength_increase_range,
            'enemy_healing_increase_range' => $gameLocationGemParamter->enemy_healing_increase_range,
            'enemy_spell_evasion_range' => $gameLocationGemParamter->enemy_spell_evasion_range,
            'enemy_affix_resistance_range' => $gameLocationGemParamter->enemy_affix_resistance_range,
            'enemy_entrancing_chance_range' => $gameLocationGemParamter->enemy_entrancing_chance_range,
            'enemy_devouring_light_chance_range' => $gameLocationGemParamter->enemy_devouring_light_chance_range,
            'enemy_devouring_darkness_chance_range' => $gameLocationGemParamter->enemy_devouring_darkness_chance_range,
            'enemy_ambush_chance_range' => $gameLocationGemParamter->enemy_ambush_chance_range,
            'enemy_ambush_resistance_range' => $gameLocationGemParamter->enemy_ambush_resistance_range,
            'enemy_counter_chance_range' => $gameLocationGemParamter->enemy_counter_chance_range,
            'enemy_counter_resistance_range' => $gameLocationGemParamter->enemy_counter_resistance_range,
            'enemy_quest_item_drop_chance_increase_range' => $gameLocationGemParamter->enemy_quest_item_drop_chance_increase_range,
            'monster_xp_increase_range' => $gameLocationGemParamter->monster_xp_increase_range,
            'monster_gold_drop_increase_range' => $gameLocationGemParamter->monster_gold_drop_increase_range,
            'monster_atonement' => $gameLocationGemParamter->monster_atonement,
            'monster_atonement_range' => $gameLocationGemParamter->monster_atonement_range,
        ];
    }
}
