<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KingdomsSettleRequest extends FormRequest
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
            'name'           => 'required|min:5|max:30',
            'x_position'     => 'required|integer',
            'y_position'     => 'required|integer',
            'kingdom_amount' => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'name.required'           => 'Name is required.',
            'name.max'                => 'Name can only be 30 characters long.',
            'name.min'                => 'Name must be 5 characters long at least.',
            'x_position.required'     => 'Missing x position.',
            'y_position.required'     => 'Missing y position.',
            'kingdom_amount.required' => 'Missing current kingdom amount',
        ];
    }
}
