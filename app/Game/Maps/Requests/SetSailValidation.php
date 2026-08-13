<?php

namespace App\Game\Maps\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetSailValidation extends FormRequest
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
    public function rules(): array
    {
        return [
            'x' => 'required|integer',
            'y' => 'required|integer',
            'cost' => 'required|integer',
            'timeout' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'x.required' => 'X position is required.',
            'y.required' => 'Y position is required.',
            'cost.required' => 'Cost is required.',
            'timeout.required' => 'Timeout is required.',
        ];
    }
}
