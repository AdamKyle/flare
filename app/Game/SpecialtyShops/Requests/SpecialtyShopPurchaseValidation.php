<?php

namespace App\Game\SpecialtyShops\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpecialtyShopPurchaseValidation extends FormRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        return [
            'item_id' => 'required|integer|exists:items,id',
            'type'    => 'required|string',
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array {
        return [
            'item_id.required' => 'What are you trying to buy?',
            'type'             => 'What is the specialty shop type?',
        ];
    }
}
