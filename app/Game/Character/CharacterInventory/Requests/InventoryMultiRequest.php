<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryMultiRequest extends FormRequest
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
            'slot_ids'    => 'array|required',
            'slot_ids.*'  => 'integer',
        ];
    }

    public function messages() {
        return [
            'slot_ids.*.integer'   => 'must be a set of slot ids',
            'slot_ids.required'    => 'missing slot ids array',
        ];
    }
}
