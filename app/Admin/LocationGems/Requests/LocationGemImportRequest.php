<?php

namespace App\Admin\LocationGems\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationGemImportRequest extends FormRequest
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
     * Return validation rules for the Location Gems workbook upload.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'location_gems_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Location-Gems-specific workbook validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location_gems_import.required' => 'Select a Location Gems workbook to import.',
            'location_gems_import.file' => 'The Location Gems import must be a file.',
            'location_gems_import.mimes' => 'The Location Gems import must be an XLSX or XLS workbook.',
            'location_gems_import.max' => 'The Location Gems workbook may not be larger than 2MB.',
        ];
    }
}
