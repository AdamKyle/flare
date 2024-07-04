<?php

namespace App\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class EventPageRequest extends FormRequest
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
            'event_type'      => 'required|int',
        ];
    }

    public function messages() {
        return [
            'event_type.required' => 'Event type is required.',
        ];
    }
}
