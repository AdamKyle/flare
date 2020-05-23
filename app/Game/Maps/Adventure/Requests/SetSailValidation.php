<?php

namespace App\Game\Maps\Adventure\Requests;

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
            'current_port_id' => 'required',
            'cost'            => 'required',
            'time_out_value'  => 'required',
        ];
    }

    public function messages() {
        return [
            'current_port_id.required' => 'Current Port Is required.',
            'cost.required'            => 'Cost is required.',
            'time_out_value.required'  => 'Time out value is required.',
        ];
    }
}
