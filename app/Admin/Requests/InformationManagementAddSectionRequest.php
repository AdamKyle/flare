<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InformationManagementAddSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the input for validatn.
     * This ensures that JSON stringified data is converted into an array.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('section_to_insert') && is_string($this->section_to_insert)) {
            $decodedSections = json_decode($this->section_to_insert, true);

            if (is_array($decodedSections)) {
                $this->merge(['section_to_insert' => $decodedSections]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page_id'   => 'required|integer|exists:info_pages,id',
            'section_to_insert'  => 'required|array',
            'section_to_insert.order' => 'required|integer|min:0',
            'section_to_insert.content' => 'nullable|string',
            'section_to_insert.content_image_path' => 'nullable|string',
            'section_to_insert.live_wire_component' => 'nullable|string',
            'section_to_insert.item_table_type' => 'nullable|string',
            'section_to_insert.is_new_section' => 'nullable|boolean',
            'section_to_insert.insert_at_index' => 'nullable|integer|min:0'
        ];
    }

    /**
     * Messages for validation.
     */
    public function messages(): array
    {
        return [
            'page_id.required'    => 'Page ID is required.',
            'page_id.exists'      => 'The provided Page ID does not exist.',
            'sections.required'   => 'Sections array is required.',
            'sections.array'      => 'Sections must be an array.',
            'sections.order.required' => 'Each section must have an order.',
            'sections.order.integer'  => 'Order must be an integer.',
            'sections.new_order.integer' => 'New order must be an integer if provided.',
            'sections.content.string' => 'Content must be a string.',
            'sections.content_image_path.string' => 'Content image path must be a string.',
            'sections.live_wire_component.string' => 'Livewire component must be a string.',
            'sections.item_table_type.string' => 'Item table type must be a string.',
            'sections.is_new_section.boolean' => 'is_new_section must be a boolean.',
        ];
    }
}
