<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveItemRequest extends FormRequest
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
            'set_id' => 'integer|required',
            'slot_id' => 'integer|required',
        ];
    }

    public function messages()
    {
        return [
            'set_id.integer' => 'The set id must be an integer',
            'set_id.required' => 'The set id is required',
            'slot_id.integer' => 'The slot id must be an integer',
            'slot_id.required' => 'The slot id is required',
        ];
    }
}
