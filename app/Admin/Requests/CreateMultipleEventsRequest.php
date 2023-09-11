<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class CreateMultipleEventsRequest extends FormRequest
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
            'event_generation_times' => 'required|integer',
            'generate_every'         => 'required|string',
            'selected_event_type'    => 'required|integer',
            'selected_start_date'    => 'required',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'event_generation_times.required' => 'Missing how many times this event should generate',
            'generate_every.required'         => 'Missing how often this event runs.',
            'selected_event_type.required'    => 'Missing selected event type.',
            'selected_start_date.required'    => 'Missing selected start date of the event.'
        ];
    }
}
