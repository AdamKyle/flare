<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class MoveLocationRequest extends FormRequest
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
            'x' => 'required|integer',
            'y' => 'required|integer',
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
            'x.required' => 'Missing x position',
            'y.required' => 'Missing y position',
        ];
    }
}
