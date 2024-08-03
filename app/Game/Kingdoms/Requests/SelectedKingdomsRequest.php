<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectedKingdomsRequest extends FormRequest
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
            'selected_kingdoms' => 'required|array',
            'selected_kingdoms.*' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'selected_kingdoms.required' => 'Selected kingdoms is required.',
            'selected_kingdoms.array' => 'Selected kingdoms must be an array.',
            'selected_kingdoms.*.required' => 'Selected kingdoms cannot be an empty array',
            'selected_kingdoms.*.integer' => 'Selected kingdoms array values must be of type int.',
        ];
    }
}
