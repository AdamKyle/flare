<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class RaidImportRequest extends FormRequest
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
            'raids' => 'required|mimes:xlsx|max:2048',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'raids.required' => 'Raid import file is required.',
            'raids.mime' => 'The system only accepts xlsx files.',
            'raids.max' => 'File to large, the system only accepts a max size of 2MB.',
        ];
    }
}
