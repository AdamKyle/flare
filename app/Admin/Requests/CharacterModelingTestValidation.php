<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CharacterModelingTestValidation extends FormRequest
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
            'model_id'         => 'required',
            'type'             => 'required',
            'characters'       => 'required',
            'character_levels' => 'required',
            'total_times'      => 'required',
        ];
    }

    /**
     * Messages for the validation.
     * 
     * @return array
     */
    public function messages() {
        return [
            'model_id.required'         => 'Model Id is required.',
            'type.required'             => 'Type is required.',
            'characters.required'       => 'You need at least one character to test this.',
            'character_levels.required' => 'Character levels are required.',
            'total_times.required'      => 'How many times do you want this simmulation to run?',
        ];
    }
}
