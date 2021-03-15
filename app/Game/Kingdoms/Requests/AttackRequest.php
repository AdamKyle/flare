<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttackRequest extends FormRequest
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
            'defender_id'         => 'required|integer',
            'units_to_send.*'     => 'required|array',
        ];
    }

    public function messages() {
        return [
            'defender_id.required'         => 'Defender id is required',
            'defender_id.integer'          => 'Defender id must be an integer.',
            'units_to_send.*.required'     => 'Units is required.',
            'units_to_send.*.array'        => 'Units to send must be an array.',
        ];
    }
}
