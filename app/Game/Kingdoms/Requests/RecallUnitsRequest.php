<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecallUnitsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
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
            'unit_movement_queue_id' => 'required|int|exists:unit_movement_queue,id',
        ];
    }

    public function messages() {
        return [
            'unit_movement_queue_id.required' => 'Unit Movement Queue is required.',
        ];
    }
}
