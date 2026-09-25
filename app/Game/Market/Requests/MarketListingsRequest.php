<?php

namespace App\Game\Market\Requests;

use App\Game\Market\Enums\MarketListingPriceSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarketListingsRequest extends FormRequest
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
     * Get the validation rules for browsing Market listings.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'per_page' => 'required|integer|min:1',
            'page' => 'required|integer|min:1',
            'search_text' => 'nullable|string',
            'filters' => 'array',
            'filters.type' => 'nullable|string',
            'filters.sort_price' => ['nullable', Rule::enum(MarketListingPriceSort::class)],
        ];
    }

    /**
     * Apply the default pagination values before validation.
     *
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page') ?? 10,
            'page' => $this->input('page') ?? 1,
            'search_text' => $this->input('search_text') ?? '',
            'filters' => $this->input('filters') ?? [],
        ]);
    }
}
