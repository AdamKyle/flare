<?php

namespace App\Game\Automation\FactionLoyalty\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FactionLoyaltyAutomationRequest extends FormRequest
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
            'attack_type' => 'required|string',
        ];
    }

    /**
     * Get the custom validation messages for the request rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'attack_type.required' => 'Invalid input.',
        ];
    }
}
