<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyAllFromSetRequest extends FormRequest
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
            'set_id' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'set_id.required' => 'Which set do you want to destroy all items from?',
        ];
    }
}
