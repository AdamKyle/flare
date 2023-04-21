<?php

namespace App\Game\NpcActions\SeerActions\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddGemToItemRequest extends FormRequest
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
            'slot_id'     => 'required|integer',
            'gem_slot_id' => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'slot_id.required'     => 'Error. Invalid Input.',
            'gem_slot_id.required' => 'Error. Invalid Input',
        ];
    }
}
