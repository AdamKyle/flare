<?php

namespace App\Game\Battle\Request;

use Illuminate\Foundation\Http\FormRequest;

class RankedFightRequest extends FormRequest
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
            'attack_type' => 'string|required|in:attack,cast,attack_and_cast,cast_and_attack,defend'
        ];
    }

    public function messages() {
        return [
            'attack_type.in' => 'Invalid Input.'
        ];
    }
}
