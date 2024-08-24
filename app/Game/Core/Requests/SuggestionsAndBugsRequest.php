<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuggestionsAndBugsRequest extends FormRequest
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
            'title' => 'required|unique:suggestion_and_bugs,title|string|max:255',
            'type' => 'required|string|in:bug,suggestion',
            'platform' => 'required|string|in:mobile,desktop,both',
            'description' => 'required|string',
            'files' => 'array',
            'files.*' => 'file|mimes:jpg,jpeg,png,gif|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The title is required.',
            'type.required' => 'The type is required.',
            'platform.required' => 'The platform is required.',
            'description.required' => 'The description is required.',
            'files.*.file' => 'Each file must be a valid file.',
            'files.*.mimes' => 'Each file must be a file of type: jpg, jpeg, png, gif.',
            'files.*.max' => 'Each file may not be greater than 2048 kilobytes.',
        ];
    }
}
