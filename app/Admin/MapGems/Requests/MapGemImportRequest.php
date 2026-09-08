<?php

namespace App\Admin\MapGems\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapGemImportRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for the Map Gems workbook upload.
     */
    public function rules(): array
    {
        return [
            'map_gems_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Map-Gems-specific workbook validation messages.
     */
    public function messages(): array
    {
        return [
            'map_gems_import.required' => 'Select a Map Gems workbook to import.',
            'map_gems_import.file' => 'The Map Gems import must be a file.',
            'map_gems_import.mimes' => 'The Map Gems import must be an XLSX or XLS workbook.',
            'map_gems_import.max' => 'The Map Gems workbook may not be larger than 2MB.',
        ];
    }
}
