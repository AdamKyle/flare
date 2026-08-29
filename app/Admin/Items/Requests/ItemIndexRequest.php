<?php

namespace App\Admin\Items\Requests;

use App\Admin\Items\Values\ItemProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ItemIndexRequest extends FormRequest
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
            'profile' => ['required', 'string', Rule::enum(ItemProfile::class)],
            'sort_key' => 'required|string',
            'sort_direction' => 'required|string|in:asc,desc',
        ];
    }

    /**
     * Apply the Items list defaults before validation runs.
     *
     * @return void Merges default list parameters into the request input.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 15),
            'page' => $this->input('page', 1),
            'search_text' => $this->input('search_text', ''),
            'profile' => $this->input('profile', ItemProfile::ALL->value),
            'sort_key' => $this->input('sort_key', 'name'),
            'sort_direction' => $this->input('sort_direction', 'asc'),
        ]);
    }

    /**
     * Configure the validator instance to enforce profile-scoped sort keys.
     *
     * @param  Validator  $validator  Validator instance to configure.
     * @return void Registers the profile-scoped sort-key validation callback.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $profile = ItemProfile::tryFrom($this->input('profile'));
            $sortKey = $this->input('sort_key');

            if (is_null($profile) || ! is_string($sortKey)) {
                return;
            }

            if (! in_array($sortKey, $profile->allowedSortKeys(), true)) {
                $validator->errors()->add('sort_key', 'The selected sort key is not allowed for this Item profile.');
            }
        });
    }
}
