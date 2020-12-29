<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KingdomsLocationRequest extends FormRequest
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
            'x_position'   => 'required',
            'y_position'   => 'required',
        ];
    }

    public function messages() {
        return [
            'x_position.required'    => 'Missing x position.',
            'y_position.required'    => 'Missing y position.',
        ];
    }
}
