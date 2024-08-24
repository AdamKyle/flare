<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenameSetRequest extends FormRequest
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
            'set_id' => 'required|exists:inventory_sets,id',
            'set_name' => 'required|string|min:5|max:30',
        ];
    }

    public function messages()
    {
        return [
            'set_id.required' => 'Which set do you want to change the name of?',
            'set_name.required' => 'Set name is needed',
            'set_name.min' => 'Set Name must be 5 characters minimum',
            'set_name.max' => 'Set Name may be 30 characters max',
        ];
    }
}
