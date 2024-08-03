<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NameTagRequest extends FormRequest
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
            'name_tag' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [];
    }
}
