<?php

namespace App\Admin\Monsters\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MonsterImportRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for the Monsters workbook upload.
     */
    public function rules(): array
    {
        return [
            'monsters_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Monsters-specific workbook validation messages.
     */
    public function messages(): array
    {
        return [
            'monsters_import.required' => 'Select a Monsters workbook to import.',
            'monsters_import.file' => 'The Monsters import must be a file.',
            'monsters_import.mimes' => 'The Monsters import must be an XLSX or XLS workbook.',
            'monsters_import.max' => 'The Monsters workbook may not be larger than 2MB.',
        ];
    }
}
