<?php

namespace App\Game\SpecialtyShops\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpecialtyShopValidation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string',
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'type.required' => 'What type from the shop?',
        ];
    }
}
