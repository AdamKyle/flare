<?php

namespace App\Admin\Locations\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationImportRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for the Locations workbook upload.
     */
    public function rules(): array
    {
        return [
            'locations_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Locations-specific workbook validation messages.
     */
    public function messages(): array
    {
        return [
            'locations_import.required' => 'Select a Locations workbook to import.',
            'locations_import.file' => 'The Locations import must be a file.',
            'locations_import.mimes' => 'The Locations import must be an XLSX or XLS workbook.',
            'locations_import.max' => 'The Locations workbook may not be larger than 2MB.',
        ];
    }
}
