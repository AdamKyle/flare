<?php

namespace App\Game\NpcActions\WorkBench\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyHolyOilRequest extends FormRequest
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
            'item_id' => 'required|integer',
            'alchemy_slot_id' => 'required_without:alchemy_item_id|integer',
            'alchemy_item_id' => 'required_without:alchemy_slot_id|integer',
        ];
    }

    public function messages()
    {
        return [
            'item_id.required' => 'Error. Invalid Input.',
            'alchemy_slot_id.required_without' => 'Error. Invalid Input.',
            'alchemy_item_id.required_without' => 'Error. Invalid Input.',
        ];
    }
}
