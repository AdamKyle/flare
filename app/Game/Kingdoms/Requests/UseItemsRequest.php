<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UseItemsRequest extends FormRequest
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
            'slots_selected' => 'required|array',
            'defender_id'    => 'required|exists:kingdoms,id',
        ];
    }

    public function messages() {
        return [
            'slots_selected.required' => 'Slots selected is required',
            'slots_selected.array'    => 'Slots selected must be an array',
            'defender_id.required'    => 'Missing defender id.',
            'defender_id.exists'      => 'Defender does not exist.',
        ];
    }
}
