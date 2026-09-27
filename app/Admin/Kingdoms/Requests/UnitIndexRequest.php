<?php

namespace App\Admin\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitIndexRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the validation rules for the Admin Kingdom Units list.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'per_page' => 'required|integer|min:1|max:100',
            'page' => 'required|integer|min:1',
            'search_text' => 'nullable|string|max:255',
            'sort_key' => ['required', 'string', Rule::in(['name', 'attack', 'defence', 'time_to_recruit', 'is_special'])],
            'sort_direction' => 'required|string|in:asc,desc',
        ];
    }

    /**
     * Return the Kingdom Units list validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'sort_key.in' => 'The selected sort key is not allowed for Kingdom Units.',
        ];
    }

    /**
     * Apply the Kingdom Units list defaults before validation runs.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 15),
            'page' => $this->input('page', 1),
            'search_text' => $this->input('search_text', ''),
            'sort_key' => $this->input('sort_key', 'name'),
            'sort_direction' => $this->input('sort_direction', 'asc'),
        ]);
    }
}
