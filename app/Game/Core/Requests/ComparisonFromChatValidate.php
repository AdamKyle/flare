<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComparisonFromChatValidate extends FormRequest
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
            'id' => 'required',
        ];
    }

    public function messages() {
        return [
            'slot_id.required' => 'Error. Invalid Input.',
        ];
    }
}
