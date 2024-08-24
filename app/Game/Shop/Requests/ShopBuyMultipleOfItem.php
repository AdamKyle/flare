<?php

namespace App\Game\Shop\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopBuyMultipleOfItem extends FormRequest
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
            'item_name' => 'required|string|exists:items,name',
        ];
    }

    public function messages()
    {
        return [
            'item_name.required' => 'What are you trying to buy multiple of child?',
            'item_name.exists' => 'Item does not exist at all ...Huh?',
        ];
    }
}
