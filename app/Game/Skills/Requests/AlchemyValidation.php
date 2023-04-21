<?php

namespace App\Game\Skills\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlchemyValidation extends FormRequest
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
            'item_to_craft'   => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'item_to_craft.required' => 'What item are you trying to craft?',
        ];
    }
}
