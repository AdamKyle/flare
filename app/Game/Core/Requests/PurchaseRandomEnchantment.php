<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRandomEnchantment extends FormRequest
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
            'type' => 'required|in:basic,medium,legendary',
        ];
    }

    public function messages() {
        return [
            'type.required' => 'Error. Invalid Input.',
        ];
    }
}
