<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapUploadValidation extends FormRequest
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
            'name' => 'required',
            'map'  => 'required|image|mimes:jpeg|max:2000'
        ];
    }

    public function messages() {
        return [
            'name.required'  => 'Map name is required.',
            'map.required'   => 'Map upload is required.',
            'map.image'      => 'Map must be an image.',
            'map.mimes:jpeg' => 'Map must be of type Jpeg.',
            'map.max:2000'   => 'Map must be be smaller. Max upload is 2 Mbs.'
        ];
    }
}
