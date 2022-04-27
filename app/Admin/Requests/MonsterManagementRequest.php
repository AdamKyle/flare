<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class MonsterManagementRequest extends FormRequest
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
            'name'                      => 'required',
            'damage_stat'               => 'required',
            'xp'                        => 'required',
            'str'                       => 'required',
            'dur'                       => 'required',
            'dex'                       => 'required',
            'chr'                       => 'required',
            'int'                       => 'required',
            'agi'                       => 'required',
            'focus'                     => 'required',
            'ac'                        => 'required',
            'gold'                      => 'required',
            'max_level'                 => 'required',
            'health_range'              => 'required',
            'attack_range'              => 'required',
            'drop_check'                => 'required',
            'game_map_id'               => 'required',
            'is_celestial_entity'       => 'nullable',
            'can_cast'                  => 'nullable',
            'gold_cost'                 => 'nullable',
            'gold_dust_cost'            => 'nullable',
            'can_use_artifacts'         => 'nullable',
            'max_spell_damage'          => 'nullable',
            'max_artifact_damage'       => 'nullable',
            'shards'                    => 'nullable',
            'spell_evasion'             => 'nullable',
            'artifact_annulment'        => 'nullable',
            'affix_resistance'          => 'nullable',
            'healing_percentage'        => 'nullable',
            'max_affix_damage'          => 'nullable',
            'entrancing_chance'         => 'nullable',
            'devouring_light_chance'    => 'nullable',
            'devouring_darkness_chance' => 'nullable',
            'accuracy'                  => 'nullable',
            'casting_accuracy'          => 'nullable',
            'dodge'                     => 'nullable',
            'criticality'               => 'nullable',
            'quest_item_id'             => 'nullable',
            'quest_item_drop_chance'    => 'nullable',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'max_level.required'    => 'Max level must be set.',
            'health_range.required' => 'Health range must be set.',
            'attack_range.required' => 'Attack range must be set.',
            'drop_check.required'   => 'Drop Check must be set.',
            'damage_stat.required'  => 'Damage stat is missing',
            'game_map_id.required'  => 'What map is this monster for?',
        ];
    }
}
