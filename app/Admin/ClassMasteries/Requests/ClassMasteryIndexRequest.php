<?php

namespace App\Admin\ClassMasteries\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ClassMasteryIndexRequest extends FormRequest
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
            'per_page' => 'required|integer|min:1|max:100',
            'page' => 'required|integer|min:1',
            'search_text' => 'nullable|string|max:255',
            'sort_key' => 'required|string',
            'sort_direction' => 'required|string|in:asc,desc',
            'filters' => 'nullable|array',
            'filters.game_class_id' => 'nullable|integer|exists:game_classes,id',
        ];
    }

    /**
     * Apply the Class Masteries list defaults before validation runs.
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

    /**
     * Configure the validator instance to enforce the allowed sort-key set.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sortKey = $this->input('sort_key');
            $allowedSortKeys = ['name', 'requires_class_rank_level', 'specialty_damage'];

            if (is_string($sortKey) && ! in_array($sortKey, $allowedSortKeys, true)) {
                $validator->errors()->add('sort_key', 'The selected sort key is not allowed for Class Masteries.');
            }
        });
    }
}
