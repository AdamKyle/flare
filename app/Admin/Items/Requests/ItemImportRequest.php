<?php

namespace App\Admin\Items\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemImportRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for the Items workbook upload.
     */
    public function rules(): array
    {
        return [
            'items_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Items-specific workbook validation messages.
     */
    public function messages(): array
    {
        return [
            'items_import.required' => 'Select an Items workbook to import.',
            'items_import.file' => 'The Items import must be a file.',
            'items_import.mimes' => 'The Items import must be an XLSX or XLS workbook.',
            'items_import.max' => 'The Items workbook may not be larger than 2MB.',
        ];
    }
}
