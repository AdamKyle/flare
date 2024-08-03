<?php

namespace App\Game\Messages\Request;

use Illuminate\Foundation\Http\FormRequest;

class PublicMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
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
            'message' => 'required|string|max:240',
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'message.required' => 'You cannot post empty messages.',
            'message.string' => 'Message must be a string.',
            'message.max' => 'Message is too long. Max is 240 characters.',
        ];
    }
}
