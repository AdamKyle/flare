<?php

namespace App\Game\Battle\Request;

use Illuminate\Foundation\Http\FormRequest;

class PvpFightInfo extends FormRequest
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
            'defender_id'  => 'int|required'
        ];
    }

    public function messages() {
        return [
            'defender_id.required'  => 'Error. Invalid Input',
        ];
    }
}
