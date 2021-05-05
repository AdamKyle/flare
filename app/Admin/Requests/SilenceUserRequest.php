<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SilenceUserRequest extends FormRequest
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
            'for'     => 'required|string',
            'user_id' => 'required|integer|exists:users,id'
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'for.required'     => 'Length of time to silence is required.',
            'user_id.required' => 'User is required',
        ];
    }
}
