<?php

namespace App\Admin\Quests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestImportRequest extends FormRequest
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
     * Return validation rules for the Quests workbook upload.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'quests_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Quests-specific workbook validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quests_import.required' => 'Select a Quests workbook to import.',
            'quests_import.file' => 'The Quests import must be a file.',
            'quests_import.mimes' => 'The Quests import must be an XLSX or XLS workbook.',
            'quests_import.max' => 'The Quests workbook may not be larger than 2MB.',
        ];
    }
}
