<?php

namespace App\Admin\ClassMasteries\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassMasteryImportRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for the Class Masteries workbook upload.
     */
    public function rules(): array
    {
        return [
            'class_masteries_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Class-Masteries-specific workbook validation messages.
     */
    public function messages(): array
    {
        return [
            'class_masteries_import.required' => 'Select a Class Masteries workbook to import.',
            'class_masteries_import.file' => 'The Class Masteries import must be a file.',
            'class_masteries_import.mimes' => 'The Class Masteries import must be an XLSX or XLS workbook.',
            'class_masteries_import.max' => 'The Class Masteries workbook may not be larger than 2MB.',
        ];
    }
}
