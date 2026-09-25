<?php

namespace App\Game\Shop\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoblinShopPurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for buying an amount of a Goblin Shop Item.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1',
        ];
    }

    /**
     * Get the validation messages for the purchase amount.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Choose how many to buy.',
            'amount.integer' => 'The amount to buy must be a whole number.',
            'amount.min' => 'You must buy at least one.',
        ];
    }
}
