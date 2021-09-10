<?php

namespace App\Game\Messages\Request;

use Illuminate\Foundation\Http\FormRequest;

class PublicEntityRequest extends FormRequest
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
            'attempt_to_teleport' => 'required|boolean',
        ];
    }

    public function messages() {
        return [
            'attempt_to_teleport.required' => 'Are you attempting to teleport?',
        ];
    }
}
