<?php

namespace App\Game\Maps\Adventure\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeleportValidation extends FormRequest
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
            'x'       => 'required',
            'y'       => 'required',
            'cost'    => 'required',
            'timeout' => 'required',
        ];
    }

    public function messages() {
        return [
            'x.required'       => 'X position is required.',
            'y.required'       => 'Y position is required.',
            'cost.required'    => 'Cost is required.',
            'timeout.required' => 'Timeout is required.'
        ];
    }
}
