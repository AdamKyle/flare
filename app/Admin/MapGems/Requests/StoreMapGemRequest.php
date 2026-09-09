<?php

namespace App\Admin\MapGems\Requests;

use App\Admin\Requests\Concerns\HasGemRangeValidation;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMapGemRequest extends FormRequest
{
    use HasGemRangeValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'game_map_id' => [
                'required',
                'integer',
                Rule::exists('game_maps', 'id')->whereNull('generated_map_type'),
                Rule::unique('game_map_gem_paramters', 'game_map_id')->ignore($this->route('gameMapGemParamter')?->id),
            ],
            'monster_atonement' => ['nullable', 'integer', Rule::in(array_keys(GemTypeValue::getNames()))],
            'crafting_skill_ids' => ['nullable', 'array'],
            'crafting_skill_ids.*' => ['integer', Rule::exists('game_skills', 'id')->where('can_train', false)],
        ];

        foreach ($this->rangeFields() as $rangeField) {
            $rules[$rangeField] = $this->rangeRule();
        }

        return $rules;
    }

    /**
     * Return every range field managed by the Map Gem form.
     */
    protected function rangeFields(): array
    {
        return [
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
            'monster_atonement_range',
        ];
    }
}
