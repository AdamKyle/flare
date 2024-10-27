<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CapitalCityCancelBuildingRequest extends FormRequest
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
            'building_id' => 'integer|nullable',
            'queue_id' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'queue_id.required' => 'Queue id is missing',
            'queue_id.integer' => 'Queue id must be an intger',
            'building_id.integer' => 'Building id must be an integer',
        ];
    }
}
