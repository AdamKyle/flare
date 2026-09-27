<?php

namespace App\Admin\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KingdomImportRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for the Kingdom workbook upload.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'kingdom_import' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ];
    }

    /**
     * Return Kingdom-specific workbook validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'kingdom_import.required' => 'Select a Kingdom workbook to import.',
            'kingdom_import.file' => 'The Kingdom import must be a file.',
            'kingdom_import.mimes' => 'The Kingdom import must be an XLSX workbook.',
            'kingdom_import.max' => 'The Kingdom workbook may not be larger than 2MB.',
        ];
    }
}
