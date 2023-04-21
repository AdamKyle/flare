<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class ItemsManagementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                             => 'required',
            'type'                             => 'required',
            'description'                      => 'required',
            'can_drop'                         => 'nullable',
            'craft_only'                       => 'nullable',
            'default_position'                 => 'nullable',
            'base_damage'                      => 'nullable',
            'base_ac'                          => 'nullable',
            'base_healing'                     => 'nullable',
            'can_craft'                        => 'nullable',
            'crafting_type'                    => 'nullable',
            'cost'                             => 'nullable',
            'gold_dust_cost'                   => 'nullable',
            'shards_cost'                      => 'nullable',
            'skill_level_required'             => 'nullable',
            'skill_level_trivial'              => 'nullable',
            'skill_name'                       => 'nullable',
            'skill_bonus'                      => 'nullable',
            'base_damage_mod_bonus'            => 'nullable',
            'base_healing_mod_bonus'           => 'nullable',
            'base_ac_mod_bonus'                => 'nullable',
            'fight_time_out_mod_bonus'         => 'nullable',
            'move_time_out_mod_bonus'          => 'nullable',
            'skill_training_bonus'             => 'nullable',
            'market_sellable'                  => 'nullable',
            'usable'                           => 'nullable',
            'damages_kingdoms'                 => 'nullable',
            'kingdom_damage'                   => 'nullable',
            'lasts_for'                        => 'nullable',
            'stat_increase'                    => 'nullable',
            'increase_stat_by'                 => 'nullable',
            'affects_skill_type'               => 'nullable',
            'increase_skill_bonus_by'          => 'nullable',
            'increase_skill_training_bonus_by' => 'nullable',
            'can_resurrect'                    => 'nullable',
            'resurrection_chance'              => 'nullable',
            'spell_evasion'                    => 'nullable',
            'artifact_annulment'               => 'nullable',
            'healing_reduction'                => 'nullable',
            'affix_damage_reduction'           => 'nullable',
            'devouring_light'                  => 'nullable',
            'devouring_darkness'               => 'nullable',
            'drop_location_id'                 => 'nullable',
            'xp_bonus'                         => 'nullable',
            'ignores_caps'                     => 'nullable',
            'can_use_on_other_items'           => 'nullable',
            'holy_level'                       => 'nullable',
            'base_damage_mod'                  => 'nullable',
            'base_healing_mod'                 => 'nullable',
            'base_ac_mod'                      => 'nullable',
            'str_mod'                          => 'nullable',
            'dur_mod'                          => 'nullable',
            'dex_mod'                          => 'nullable',
            'chr_mod'                          => 'nullable',
            'int_mod'                          => 'nullable',
            'agi_mod'                          => 'nullable',
            'focus_mod'                        => 'nullable',
            'effect'                           => 'nullable',
            'fight_time_out_mod_bonus'         => 'nullable',
            'base_damage_mod_bonus'            => 'nullable',
            'base_healing_mod_bonus'           => 'nullable',
            'base_ac_mod_bonus'                => 'nullable',
            'move_time_out_mod_bonus'          => 'nullable',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'name.required'        => 'Missing Name',
            'type.required'        => 'Missing Type',
            'description.required' => 'Missing Description'
        ];
    }
}
