<?php

namespace App\Admin\Races\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRaceRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2000',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Race name.',
            'image.image' => 'The Race image upload must be an image.',
            'image.max' => 'The Race image may not be larger than 2MB.',
        ];
    }
}
