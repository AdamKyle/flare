<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class InfoImport extends FormRequest
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
            'info_import' => 'required|file|mimetypes:application/json',
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
            'info_import.required' => 'Items import file is required.',
            'info_import.mime' => 'The system only accepts json files.',
            'info_import.max' => 'File to large, the system only accepts a max size of 2MB.',
        ];
    }
}
