<?php

namespace App\Admin\GameMaps\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameMapImportRequest extends FormRequest
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
     * Return validation rules for the Game Maps workbook upload.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'game_maps_import' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ];
    }

    /**
     * Return Game Maps-specific workbook validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'game_maps_import.required' => 'Select a Game Maps workbook to import.',
            'game_maps_import.file' => 'The Game Maps import must be a file.',
            'game_maps_import.mimes' => 'The Game Maps import must be an XLSX or XLS workbook.',
            'game_maps_import.max' => 'The Game Maps workbook may not be larger than 2MB.',
        ];
    }
}
