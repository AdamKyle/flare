<?php

namespace App\Game\Maps\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveRequest extends FormRequest
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
            'character_position_x' => 'required|integer',
            'character_position_y' => 'required|integer',
        ];
    }

    /**
     * Get messages for the validation failure.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'character_position_x.required' => 'character x position is required.',
            'character_position_y.required' => 'character y position is required.',
        ];
    }
}
