<?php

namespace App\Flare\Pagination\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
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
            'per_page' => 'required|min:1|integer',
            'page' => 'required|min:1|integer',
            'search_text' => 'nullable|string',
            'filters' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'per_page.required' => 'How many do you want per page?',
            'page.required' => 'What page are we trying to fetch?',
        ];
    }

    public function prepareForValidation(): void
    {

        $this->merge([
            'search_text' => $this->has('search_text') && is_null($this->input('search_text'))
                ? ''
                : $this->input('search_text'),
            'filters' => ! $this->has('filters') ? [] : $this->input('filters'),
        ]);
    }
}
