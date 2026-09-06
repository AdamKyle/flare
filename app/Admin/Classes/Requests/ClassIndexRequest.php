<?php

namespace App\Admin\Classes\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ClassIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always true; authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'required|integer|min:1|max:100',
            'page' => 'required|integer|min:1',
            'search_text' => 'nullable|string|max:255',
            'sort_key' => 'required|string',
            'sort_direction' => 'required|string|in:asc,desc',
        ];
    }

    /**
     * Apply the Classes list defaults before validation runs.
     *
     * @return void Merges default list parameters into the request input.
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

    /**
     * Configure the validator instance to enforce the allowed sort-key set.
     *
     * @param  Validator  $validator  Validator instance to configure.
     * @return void Registers the allowed sort-key validation callback.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $sortKey = $this->input('sort_key');
            $allowedSortKeys = ['name', 'damage_stat', 'to_hit_stat'];

            if (is_string($sortKey) && ! in_array($sortKey, $allowedSortKeys, true)) {
                $validator->errors()->add('sort_key', 'The selected sort key is not allowed for Classes.');
            }
        });
    }
}
