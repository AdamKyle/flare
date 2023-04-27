<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class CreateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
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
            'selected_event_type' => 'required|integer',
            'event_description'   => 'required|string',
            'selected_raid'       => 'nullable|int',
            'selected_start_date' => 'required',
            'selected_end_date'   => 'required',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'selected_event_type.required' => 'Missing the schedule event type.',
            'event_description.required'   => 'Event description is missing.',
            'selected_start_date.required' => 'You must select a date.',
            'selected_end_date.required'   => 'You must select an end date.',
        ];
    }
}
