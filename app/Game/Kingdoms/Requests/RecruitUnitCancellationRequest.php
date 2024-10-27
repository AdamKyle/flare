<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecruitUnitCancellationRequest extends FormRequest
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
            'unit_name' => 'string|nullable',
            'queue_id' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'queue_id.required' => 'Which request are you trying to cancel?',
            'queue_id.integer' => 'The queue id should be an integer.',
        ];
    }
}
