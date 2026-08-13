<?php

namespace App\Game\Battle\Request;

use Illuminate\Foundation\Http\FormRequest;

class ConjureRequest extends FormRequest
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
            'monster_id' => 'required|integer',
            'type' => 'required|in:public,private',
        ];
    }

    public function messages(): array
    {
        return [
            'monster_id.required' => 'What monster are you trying to conjure?',
            'type.required' => 'Missing type.',
            'type.in' => 'Invalid input.',
        ];
    }
}
