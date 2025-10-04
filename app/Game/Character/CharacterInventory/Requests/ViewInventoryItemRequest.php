<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ViewInventoryItemRequest extends FormRequest
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
            'slot_id' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'slot_id.required' => 'You must pass in a slot id',
            'slot_id.integer' => 'slot ids must be integers.',
        ];
    }
}
