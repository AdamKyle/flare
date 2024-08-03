<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class EventsImportRequest extends FormRequest
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
            'scheduled_events' => 'required|mimes:xlsx|max:2048',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'scheduled_events.required' => 'Scheduled Events import file is required.',
            'scheduled_events.mime' => 'The system only accepts xlsx files.',
            'scheduled_events.max' => 'File to large, the system only accepts a max size of 2MB.',
        ];
    }
}
