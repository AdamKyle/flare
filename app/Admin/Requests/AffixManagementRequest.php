<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class AffixManagementRequest extends FormRequest
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
            'name'                     => 'required',
            'type'                     => 'required',
            'description'              => 'required',
            'cost'                     => 'required',
            'int_required'             => 'required',
            'skill_level_required'     => 'required',
            'skill_level_trivial'      => 'required',
            'can_drop'                 => 'nullable',
            'damage'                   => 'nullable',
            'irresistible_damage'      => 'nullable',
            'damage_can_stack'         => 'nullable',
            'devouring_light'          => 'nullable',
            'base_damage_mod'          => 'nullable',
            'base_ac_mod'              => 'nullable',
            'base_healing_mod'         => 'nullable',
            'str_mod'                  => 'nullable',
            'dur_mod'                  => 'nullable',
            'dex_mod'                  => 'nullable',
            'chr_mod'                  => 'nullable',
            'int_mod'                  => 'nullable',
            'agi_mod'                  => 'nullable',
            'focus_mod'                => 'nullable',
            'str_reduction'            => 'nullable',
            'dur_reduction'            => 'nullable',
            'dex_reduction'            => 'nullable',
            'chr_reduction'            => 'nullable',
            'int_reduction'            => 'nullable',
            'agi_reduction'            => 'nullable',
            'focus_reduction'          => 'nullable',
            'reduces_enemy_stats'      => 'nullable',
            'steal_life_amount'        => 'nullable',
            'entranced_chance'         => 'nullable',
            'skill_name'               => 'nullable',
            'skill_bonus'              => 'nullable',
            'skill_training_bonus'     => 'nullable',
            'skill_reduction'          => 'nullable',
            'resistance_reduction'     => 'nullable',
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
