<?php

namespace App\Game\Automation\Delve\Requests;

use App\Game\Core\Combat\Values\AttackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DelveExplorationRequest extends FormRequest
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
     * Get the validation rules that apply to the Delve start request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'attack_type' => ['required', 'string', Rule::enum(AttackType::class)],
            'pack_size' => 'nullable|integer',
        ];
    }

    /**
     * Get the custom validation messages for the Delve start request rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'attack_type.required' => 'Invalid input.',
            'attack_type.enum' => 'Invalid attack type was selected. Please select from the drop down.',
        ];
    }
}
