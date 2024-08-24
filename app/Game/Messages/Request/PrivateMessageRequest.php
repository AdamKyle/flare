<?php

namespace App\Game\Messages\Request;

use Illuminate\Foundation\Http\FormRequest;

class PrivateMessageRequest extends FormRequest
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
            'message' => 'required|string|max:240',
            'user_name' => 'required|string',
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
