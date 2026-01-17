<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryActionRequest extends FormRequest
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
            'item_id' => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'Item ID is required.',
            'item_id.integer' => 'Item ID must be an integer.',
        ];
    }
}
