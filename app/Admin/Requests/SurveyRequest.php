<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SurveyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sections' => 'required|array',
            'sections.*.title' => 'required_with:sections|string|max:255',
            'sections.*.description' => 'nullable|string',
            'sections.*.input_types' => 'required_with:sections|array',
            'sections.*.input_types.*.type' => 'required|string|in:text,radio,checkbox,markdown,select',
            'sections.*.input_types.*.label' => 'required_with:sections.*.input_types.*|string|max:255',
            'sections.*.input_types.*.options' => 'nullable|array',
            'sections.*.input_types.*.options.*' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The survey title is required.',
            'sections.required' => 'At least one section is required.',
            'sections.*.title.required_with' => 'Each section must have a title.',
            'sections.*.input_types.required_with' => 'Each section must have at least one field.',
            'sections.*.input_types.*.label.required_with' => 'Each field must have a label.',
        ];
    }
}
