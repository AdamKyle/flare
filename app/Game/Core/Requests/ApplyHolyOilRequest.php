<?php

namespace App\Game\Core\Requests;

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
            'item_id'         => 'required|integer',
            'alchemy_item_id' => 'required|integer',
            'gold_dust_cost'  => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'item_id.required'         => 'Error. Invalid Input.',
            'alchemy_item_id.required' => 'Error. Invalid Input.',
            'gold_dust_cost.required'  => 'Error. Invalid Input.',
        ];
    }
}
