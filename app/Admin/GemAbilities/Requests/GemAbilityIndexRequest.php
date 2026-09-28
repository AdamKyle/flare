<?php

namespace App\Admin\GemAbilities\Requests;

use App\Game\Gems\Values\GemAbilityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GemAbilityIndexRequest extends FormRequest
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
     * Get the validation rules that apply to the Gem Abilities list request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'per_page' => 'required|integer|min:1|max:100',
            'page' => 'required|integer|min:1',
            'search_text' => 'nullable|string|max:255',
            'sort_key' => ['required', 'string', Rule::in(['name', 'ability_type', 'effect_type', 'enabled'])],
            'sort_direction' => 'required|string|in:asc,desc',
            'filters' => 'nullable|array',
            'filters.ability_type' => ['nullable', Rule::enum(GemAbilityType::class)],
            'filters.enabled' => 'nullable|boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'sort_key.in' => 'The selected sort key is not allowed for Gem Abilities.',
        ];
    }

    /**
     * Apply the Gem Abilities list defaults before validation runs.
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
            'filters' => $this->input('filters', []),
        ]);
    }
}
