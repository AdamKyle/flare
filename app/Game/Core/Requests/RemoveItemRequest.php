<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveItemRequest extends FormRequest
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
            'inventory_set_id' => 'integer|required',
            'slot_id'          => 'integer|required',
        ];
    }

    public function messages() {
        return [
            'inventory_set_id.integer'  => 'The set id must be an integer',
            'inventory_set_id.required' => 'The set id is required',
            'slot_id.integer'           => 'The slot id must be an integer',
            'slot_id.required'          => 'The slot id is required',
        ];
    }
}
