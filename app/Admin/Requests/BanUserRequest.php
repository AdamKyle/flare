<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BanUserRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'ban_for' => 'required|string',
            'ban_message' => 'required|string',
            'user_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ban_for.required' => 'Ban For is required',
            'ban_message.required' => 'Ban Message is required',
            'user_id.required' => 'User is required',
        ];
    }
}
