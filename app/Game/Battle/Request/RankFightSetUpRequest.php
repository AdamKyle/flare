<?php

namespace App\Game\Battle\Request;

use Illuminate\Foundation\Http\FormRequest;

class RankFightSetUpRequest extends FormRequest
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
            'rank' => 'integer|required|gt:0',
        ];
    }

    public function messages() {
        return [
            'rank.required' => 'Error. Invalid Input.',
        ];
    }
}
