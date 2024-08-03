<?php

namespace App\Game\Maps\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraverseRequest extends FormRequest
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
            'map_id' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'map_id.required' => 'Map id is required.',
        ];
    }
}
