<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveItemRequest extends FormRequest
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
            'move_to_set' => 'required',
            'slot_id'     => 'integer|required',
        ];
    }

    public function messages() {
        return [
            'move_to_set.required' => 'Which set do you want to move this too?',
            'slot_id.integer'      => 'The slot id must be an integer',
            'slot_id.required'     => 'The slot id is required',
        ];
    }
}
