<?php

namespace App\Game\Battle\Request;

use Illuminate\Foundation\Http\FormRequest;

class FactionLoyaltyFight extends FormRequest
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
            'monster_id' => 'integer|required',
            'npc_id' => 'integer|required',
            'attack_type' => 'string|required|in:attack,cast,attack_and_cast,cast_and_attack,defend',
        ];
    }

    public function messages()
    {
        return [
            'monster_id.required' => 'What monster are you fighting?',
            'npc_id.required' => 'What npc are you helping?',
            'attack_type.required' => 'What attack type do you want?'
        ];
    }
}
