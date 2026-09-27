<?php

namespace App\Admin\Skills\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SkillImportRequest extends FormRequest
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
     * Return validation rules for the Skills workbook upload.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'skills_import' => ['required', 'file', 'mimes:xlsx', 'max:2048'],
        ];
    }

    /**
     * Return Skills-specific workbook validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'skills_import.required' => 'Select a Skills workbook to import.',
            'skills_import.file' => 'The Skills import must be a file.',
            'skills_import.mimes' => 'The Skills import must be an XLSX workbook.',
            'skills_import.max' => 'The Skills workbook may not be larger than 2MB.',
        ];
    }
}
