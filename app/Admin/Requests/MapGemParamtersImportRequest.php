<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapGemParamtersImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'map_gems_import' => ['required', 'mimes:xlsx', 'max:2048'],
        ];
    }
}
