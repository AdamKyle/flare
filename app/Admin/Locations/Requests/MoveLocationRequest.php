<?php

namespace App\Admin\Locations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveLocationRequest extends FormRequest
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
            'x' => 'required|integer',
            'y' => 'required|integer',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'x.required' => 'Select an X coordinate to move to.',
            'y.required' => 'Select a Y coordinate to move to.',
        ];
    }
}
