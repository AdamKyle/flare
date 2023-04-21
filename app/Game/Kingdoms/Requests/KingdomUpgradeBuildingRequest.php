<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KingdomUpgradeBuildingRequest extends FormRequest
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
            'to_level'         => 'required|int',
            'paying_with_gold' => 'required',
        ];
    }

    public function messages() {
        return [
            'deposit_amount.required' => 'Amount to deposit is required.',
        ];
    }
}
