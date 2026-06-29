<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationTemplatesImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location_templates_import' => 'required|mimes:xlsx|max:2048',
        ];
    }
}
