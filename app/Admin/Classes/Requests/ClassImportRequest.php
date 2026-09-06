<?php

namespace App\Admin\Classes\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassImportRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     *
     * @return bool Always true; authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for the Classes workbook upload.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'classes_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Classes-specific workbook validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'classes_import.required' => 'Select a Classes workbook to import.',
            'classes_import.file' => 'The Classes import must be a file.',
            'classes_import.mimes' => 'The Classes import must be an XLSX or XLS workbook.',
            'classes_import.max' => 'The Classes workbook may not be larger than 2MB.',
        ];
    }
}
