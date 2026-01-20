<?php

namespace App\Game\Market\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPriceRequest extends FormRequest
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
            'list_for' => 'integer|required|min:1|max:2000000000000',
            'slot_id' => 'integer|required',
        ];
    }
}
