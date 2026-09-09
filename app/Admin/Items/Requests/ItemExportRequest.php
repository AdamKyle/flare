<?php

namespace App\Admin\Items\Requests;

use App\Admin\Items\Values\ItemExportProfile;
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
            'profile' => ['required', 'string', Rule::enum(ItemExportProfile::class)],
        ];
    }

    /**
     * Resolve the validated Item export profile.
     */
    public function profile(): ItemExportProfile
    {
        return ItemExportProfile::from($this->validated('profile'));
    }
}
