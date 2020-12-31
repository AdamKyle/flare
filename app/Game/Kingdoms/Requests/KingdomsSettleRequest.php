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
            'name'         => 'required|unique:kingdoms|min:5|max:15',
            'color'        => 'required',
            'x_position'   => 'required',
            'y_position'   => 'required',
        ];
    }

    public function messages() {
        return [
            'name.required'          => 'Name is required.',
            'name.max'               => 'Name can only be 15 characters long.',
            'name.min'               => 'Name must be 4 characters long at least.',
            'x_position.required'    => 'Missing x position.',
            'y_position.required'    => 'Missing y position.',
        ];
    }
}
