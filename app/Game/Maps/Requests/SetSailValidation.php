<?php

namespace App\Game\Maps\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetSailValidation extends FormRequest
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
            'current_port_id' => 'required|integer',
            'cost'            => 'required|integer',
            'time_out_value'  => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'current_port_id.required' => 'Current Port Is required.',
            'cost.required'            => 'Cost is required.',
            'time_out_value.required'  => 'Timeout value is required.',
        ];
    }
}
