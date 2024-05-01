<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResourceRequest extends FormRequest
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
            'kingdom_requesting' => 'required|int',
            'kingdom_requesting_from' => 'required|int',
            'amount_of_resources' => 'required|int',
            'use_air_ship' => 'nullable|boolean',
            'type_of_resource' => 'required|string|in:wood,clay,stone,iron,steel,all',
        ];
    }

    public function messages() {
        return [
            'kingdom_requesting.required' => 'Who is requesting?',
            'kingdom_requesting_from.required' => 'Where is it going?',
            'amount_of_resources.required' => 'How much of the resources do we want?',
            'type_of_resource' => 'Why type of resource or resources (all) do we want?',
        ];
    }
}
