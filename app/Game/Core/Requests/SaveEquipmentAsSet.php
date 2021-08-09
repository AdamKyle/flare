<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveEquipmentAsSet extends FormRequest
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
            'move_to_set' => 'integer|required',
        ];
    }

    public function messages() {
        return [
            'move_to_set.integer'  => 'The set id must be an integer',
            'move_to_set.required' => 'The set id is required',
        ];
    }
}
