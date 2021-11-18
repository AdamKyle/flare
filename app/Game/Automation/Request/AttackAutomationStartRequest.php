<?php

namespace App\Game\Automation\Request;

use Illuminate\Foundation\Http\FormRequest;

class AttackAutomationStartRequest extends FormRequest
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
            'skill_id'                 => 'nullable|integer',
            'xp_towards'               => 'nullable|numeric',
            'auto_attack_length'       => 'required|integer',
            'move_down_the_list_every' => 'nullable|integer',
            'selected_monster_id'      => 'required|integer',
            'attack_type'              => 'required|string',
        ];
    }

    public function messages() {
        return [
            'auto_attack_length.required'  => 'Invalid input.',
            'selected_monster_id.required' => 'Invalid input.',
            'attack_type.required'         => 'Invalid input.',
        ];
    }
}
