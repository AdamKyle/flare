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
            'name'              => 'required',
            'map'               => 'required|image|max:2000',
            'kingdom_color'     => 'required|min:7',
            'only_during_event' => 'nullable|int'
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'name.required'          => 'Map name is required.',
            'map.required'           => 'Map upload is required.',
            'map.image'              => 'Map must be an image.',
            'map.max:2000'           => 'Map must be be smaller. Max upload is 2 Mbs.',
            'kingdom_color.required' => 'Kingdom color is required.',
            'kingdom_color.min'      => 'Kingdom color must be a min of 7 characters.',
        ];
    }
}
