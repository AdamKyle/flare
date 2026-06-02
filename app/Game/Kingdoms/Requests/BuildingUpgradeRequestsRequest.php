<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuildingUpgradeRequestsRequest extends FormRequest
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
            'request_data' => 'required|array',
            'request_data.*.kingdomId' => 'required|integer',
            'request_data.*.buildingIds' => 'required|array',
            'request_data.*.buildingIds.*' => 'integer',
            'request_type' => 'required|in:upgrade,repair',
        ];
    }

    public function messages()
    {
        return [
            'request_data.required' => 'Missing request data',
            'request_type.required' => 'Missing request type',
        ];
    }
}
