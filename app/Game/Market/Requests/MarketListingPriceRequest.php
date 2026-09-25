<?php

namespace App\Game\Market\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarketListingPriceRequest extends FormRequest
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
     * Get the validation rules for updating a listing price.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'listed_price' => 'required|integer|min:1',
        ];
    }

    /**
     * Get the validation messages for the listing price.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'listed_price.required' => 'Enter a listing price.',
            'listed_price.integer' => 'The listing price must be a whole number of Gold.',
            'listed_price.min' => 'The listing price must be at least 1 Gold.',
        ];
    }
}
