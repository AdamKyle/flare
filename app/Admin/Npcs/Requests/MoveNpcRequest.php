<?php

namespace App\Admin\Npcs\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveNpcRequest extends FormRequest
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
            'x_position.required' => 'Select an X coordinate to move to.',
            'y_position.required' => 'Select a Y coordinate to move to.',
        ];
    }
}
