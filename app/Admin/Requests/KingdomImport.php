<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KingdomImport extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'kingdom_import' => 'required|mimes:xlsx|max:2048'
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'kingdom_import.required'   => 'Kingdom import file is required.',
            'kingdom_import.mime'       => 'The system only accepts xlsx files.',
            'kingdom_import.max'        => 'File to large, the system only accepts a max size of 2MB.',
        ];
    }
}
