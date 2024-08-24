<?php

namespace App\Game\Messages\Request;

use Illuminate\Foundation\Http\FormRequest;

class PublicEntityRequest extends FormRequest
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
     *
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'attempt_to_teleport' => 'required|boolean',
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'attempt_to_teleport.required' => 'Are you attempting to teleport?',
        ];
    }
}
