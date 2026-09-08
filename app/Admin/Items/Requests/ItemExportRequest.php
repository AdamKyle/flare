<?php

namespace App\Admin\Items\Requests;

use App\Admin\Items\Values\ItemProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemExportRequest extends FormRequest
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
            'profile' => ['required', 'string', Rule::enum(ItemProfile::class)],
        ];
    }

    /**
     * Apply the Items export default profile before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'profile' => $this->input('profile', ItemProfile::ALL->value),
        ]);
    }

    /**
     * Resolve the validated Item export profile.
     */
    public function profile(): ItemProfile
    {
        return ItemProfile::from($this->validated('profile'));
    }
}
