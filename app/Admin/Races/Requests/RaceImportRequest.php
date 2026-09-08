<?php

namespace App\Admin\Races\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RaceImportRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for the Races workbook upload.
     */
    public function rules(): array
    {
        return [
            'races_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Races-specific workbook validation messages.
     */
    public function messages(): array
    {
        return [
            'races_import.required' => 'Select a Races workbook to import.',
            'races_import.file' => 'The Races import must be a file.',
            'races_import.mimes' => 'The Races import must be an XLSX or XLS workbook.',
            'races_import.max' => 'The Races workbook may not be larger than 2MB.',
        ];
    }
}
