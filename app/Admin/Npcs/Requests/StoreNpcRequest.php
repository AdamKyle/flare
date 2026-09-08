<?php

namespace App\Admin\Npcs\Requests;

use App\Game\Npcs\Values\NpcType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNpcRequest extends FormRequest
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
            'real_name' => 'required|string|max:255',
            'type' => ['required', 'integer', Rule::enum(NpcType::class)],
            'x_position' => 'required|integer',
            'y_position' => 'required|integer',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'real_name.required' => 'Enter an NPC name.',
            'real_name.max' => 'The NPC name may not be longer than 255 characters.',
            'type.required' => 'Select an NPC type.',
            'type.integer' => 'The selected NPC type is invalid.',
            'x_position.required' => 'Select an X coordinate for this NPC.',
            'y_position.required' => 'Select a Y coordinate for this NPC.',
        ];
    }
}
