<?php

namespace App\Admin\PassiveSkills\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PassiveSkillImportRequest extends FormRequest
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
     * Return validation rules for the Passive Skills workbook upload.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'passives_import' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ];
    }

    /**
     * Return Passive Skills-specific workbook validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'passives_import.required' => 'Select a Passive Skills workbook to import.',
            'passives_import.file' => 'The Passive Skills import must be a file.',
            'passives_import.mimes' => 'The Passive Skills import must be an XLSX workbook.',
            'passives_import.max' => 'The Passive Skills workbook may not be larger than 2MB.',
        ];
    }
}
