<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdventureValidation extends FormRequest
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
            'name'             => 'required',
            'description'      => 'required',
            'location_ids'     => 'required',
            'monster_ids'      => 'required',
            'levels'           => 'required',
            'time_per_level'   => 'required',
        ];
    }

    public function messages() {
        return [
            'name.required'             => 'Adventure name is required.',
            'location_ids.required'     => 'Adventure needs at least one location.',
            'monster_ids.required'      => 'Adventure needs at least one monster.',
            'levels.required'           => 'Adventure levels is required.',
            'time_per_level.required'   => 'Adventure time per level is required.',
        ];
    }
}
