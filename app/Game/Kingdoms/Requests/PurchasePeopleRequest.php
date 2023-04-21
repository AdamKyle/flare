<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchasePeopleRequest extends FormRequest
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
            'amount_to_purchase' => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'amount_to_purchase.required' => 'Amount to purchase is required.',
        ];
    }
}
