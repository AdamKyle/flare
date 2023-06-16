<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class ItemSkillManagementRequest extends FormRequest
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
            'id'                 => 'nullable|integer',
            'parent_id'          => 'nullable|integer',
            'name'               => 'required',
            'description'        => 'required',
            'max_level'          => 'required|integer',
            'total_kills_needed' => 'required|integer',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'name.required'               => 'Missing Name',
            'description.required'        => 'Missing Description',
            'max_levels.required'         => 'Missing leveles needed',
            'total_kills_needed.required' => 'Missing Total Kills Needed'
        ];
    }
}
