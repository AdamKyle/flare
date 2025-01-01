<?php

namespace App\Game\Character\CharacterSheet\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpecificDetailsRequest extends FormRequest
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
            'type' => 'required|string|in:health,ac,weapon_damage,spell_damage,ring_damage,heal_for',
        ];
    }

    public function messages()
    {
        return [];
    }
}
