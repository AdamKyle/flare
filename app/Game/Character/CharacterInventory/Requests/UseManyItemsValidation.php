<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UseManyItemsValidation extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'items_to_use' => ['required', 'array', 'min:1'],
            'items_to_use.*' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'items_to_use.required' => 'You must select some items to use.',
        ];
    }
}
