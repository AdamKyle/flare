<?php

namespace App\Game\Shop\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopSellValidation extends FormRequest
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
            'slot_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'slot_id.required' => 'What are you trying to sell to me child?',
        ];
    }
}
