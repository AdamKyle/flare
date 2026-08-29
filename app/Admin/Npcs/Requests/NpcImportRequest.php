<?php

namespace App\Admin\Npcs\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NpcImportRequest extends FormRequest
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
     * Return validation rules for the NPCs workbook upload.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'npcs_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return NPCs-specific workbook validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'npcs_import.required' => 'Select an NPCs workbook to import.',
            'npcs_import.file' => 'The NPCs import must be a file.',
            'npcs_import.mimes' => 'The NPCs import must be an XLSX or XLS workbook.',
            'npcs_import.max' => 'The NPCs workbook may not be larger than 2MB.',
        ];
    }
}
