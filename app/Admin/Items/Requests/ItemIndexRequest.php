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
            'profile' => ['required', 'string', Rule::enum(ItemProfile::class)],
            'subtype' => 'nullable|string',
            'sort_key' => 'required|string',
            'sort_direction' => 'required|string|in:asc,desc',
        ];
    }

    /**
     * Apply the Items list defaults before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 15),
            'page' => $this->input('page', 1),
            'search_text' => $this->input('search_text', ''),
            'profile' => $this->input('profile', ItemProfile::ALL->value),
            'subtype' => $this->input('subtype'),
            'sort_key' => $this->input('sort_key', 'name'),
            'sort_direction' => $this->input('sort_direction', 'asc'),
        ]);
    }

    /**
     * Configure the validator instance to enforce profile-scoped sort keys.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateSortKey($validator);
            $this->validateSubtype($validator);
        });
    }

    /**
     * Reject a sort key that is not allowed for the requested Item profile.
     */
    private function validateSortKey(Validator $validator): void
    {
        $profile = ItemProfile::tryFrom($this->input('profile'));
        $sortKey = $this->input('sort_key');

        if (is_null($profile) || ! is_string($sortKey)) {
            return;
        }

        if (! in_array($sortKey, $profile->allowedSortKeys(), true)) {
            $validator->errors()->add('sort_key', 'The selected sort key is not allowed for this Item profile.');
        }
    }

    /**
     * Validate the requested Item subtype against the selected Item profile.
     */
    private function validateSubtype(Validator $validator): void
    {
        $profile = ItemProfile::tryFrom($this->input('profile'));
        $subtype = $this->input('subtype');

        if (is_null($profile) || is_null($subtype)) {
            return;
        }

        $allowedSubtypes = $profile->subtypes();

        if (is_null($allowedSubtypes)) {
            $validator->errors()->add('subtype', 'This Item profile does not support a subtype filter.');

            return;
        }

        if (! in_array($subtype, $allowedSubtypes, true)) {
            $validator->errors()->add('subtype', 'The selected subtype is not valid for this Item profile.');
        }
    }
}
