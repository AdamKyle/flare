<?php

namespace App\Admin\Requests;

use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapGemParamtersManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rangeRule = ['nullable', 'string', 'max:255', 'regex:/^\s*\d+\.\d+\s*-\s*\d+\.\d+\s*$/'];
        $rangeFields = [
            'character_xp_bonus_range',
            'character_class_rank_xp_bonus_range',
            'kingdom_passive_training_reduction_range',
            'gold_gain_range',
            'gold_dust_gain_range',
            'shards_gain_range',
            'copper_coin_gain_range',
            'character_class_specialty_xp_gain_range',
            'crafting_skill_bonus_range',
            'item_drop_chance_increase_range',
            'unique_item_drop_chance_increase_range',
            'mythic_item_drop_chance_increase_range',
            'cosmic_item_drop_chance_increase_range',
            'ascended_item_drop_chance_increase_range',
            'character_power_reduction_range',
            'enemy_strength_increase_range',
            'enemy_healing_increase_range',
            'enemy_spell_evasion_range',
            'enemy_affix_resistance_range',
            'enemy_entrancing_chance_range',
            'enemy_devouring_light_chance_range',
            'enemy_devouring_darkness_chance_range',
            'enemy_ambush_chance_range',
            'enemy_ambush_resistance_range',
            'enemy_counter_chance_range',
            'enemy_counter_resistance_range',
            'enemy_quest_item_drop_chance_increase_range',
            'monster_xp_increase_range',
            'monster_gold_drop_increase_range',
            'faction_point_increase_range',
            'monster_atonement_range',
        ];

        $rules = [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'game_map_id' => [
                'required',
                'integer',
                'exists:game_maps,id',
                Rule::unique('game_map_gem_paramters', 'game_map_id')->ignore($this->integer('id')),
            ],
            'monster_atonement' => ['nullable', 'integer', Rule::in(array_keys(GemTypeValue::getNames()))],
            'crafting_skill_ids' => ['nullable', 'array'],
            'crafting_skill_ids.*' => ['integer', 'exists:game_skills,id'],
        ];

        foreach ($rangeFields as $rangeField) {
            $rules[$rangeField] = $rangeRule;
        }

        return $rules;
    }
}
