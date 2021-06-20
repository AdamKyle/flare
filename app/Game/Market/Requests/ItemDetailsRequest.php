<?php

namespace App\Game\Market\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemDetailsRequest extends FormRequest {
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
    public function rules() {
        return [
            'item_id' => 'integer|required',
        ];
    }

    public function messages() {
        return [
            'item_id.required' => 'Error. Missing item id.',
        ];
    }
}
