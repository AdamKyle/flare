<?php

namespace App\Game\Automation\Exploration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExplorationRequest extends FormRequest
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
            'auto_attack_length' => 'nullable|integer',
            'move_down_the_list_every' => 'nullable|integer',
            'selected_monster_id' => 'required|integer|exists:monsters,id',
            'attack_type' => 'nullable|string',
        ];
    }

    /**
     * Get the custom validation messages for the request rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'auto_attack_length.required' => 'Invalid input.',
            'selected_monster_id.required' => 'Select a monster to explore with.',
            'selected_monster_id.exists' => 'Select a monster to explore with.',
            'attack_type.required' => 'Invalid input.',
        ];
    }
}
