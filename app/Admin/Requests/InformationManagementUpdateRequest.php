<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InformationManagementUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => 'nullable|string',
            'page_name' => 'required|string',
            'order' => 'required|string',
            'new_order' => 'nullable|string',
            'content_image' => 'nullable|file|mimes:png|max:2000',
            'live_wire_component' => 'nullable|string',
            'item_table_type' => 'nullable|string',
            'page_id' => 'nullable|string|exists:info_pages,id',

        ];
    }

    /**
     * Messages for the validation.
     */
    public function messages(): array
    {
        return [
            'page_name' => 'Page name is missing',
            'content_image.mimetypes' => 'Images can only be PNG',
            'content_image.max' => 'Images can only be a max size of 2mb\'s',
            'live_wire_component' => 'livewire component name must be a string.',
            'page_id.exists' => 'page does not exist',
            'order' => 'Must supply an order for displaying.',
        ];
    }
}
