<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryMultiRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'mode' => 'required|in:include,all_except',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'exclude' => 'nullable|array',
            'exclude.*' => 'integer',
        ];
    }

    public function messages(): array
    {
        return [
            'mode.required' => 'The selection mode is required.',
            'mode.in' => 'The selection mode must be either include or all_except.',
            'ids.array' => 'The ids field must be an array of integers.',
            'ids.*.integer' => 'Each value in ids must be an integer.',
            'exclude.array' => 'The exclude field must be an array of integers.',
            'exclude.*.integer' => 'Each value in exclude must be an integer.',
        ];
    }
}
