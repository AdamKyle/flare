<?php

namespace App\Game\Exploration\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DelveExplorationRequest extends FormRequest
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
            'attack_type' => 'required|string',
            'pack_size' => 'nullable|integer',
        ];
    }

    public function messages()
    {
        return [
            'attack_type.required' => 'Invalid input.',
        ];
    }
}
