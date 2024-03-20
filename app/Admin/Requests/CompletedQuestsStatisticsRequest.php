<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class CompletedQuestsStatisticsRequest extends FormRequest {
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
    public function rules() {
        return [
            'type'  => 'required|in:quest,guide_quest',
            'limit' => 'integer|nullable',
            'filter' => 'in:most,some,least|nullable',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'type' => 'Invalid option',
            'limit' => 'Invalid limit',
            'filter' => 'Invalid filter',
        ];
    }
}
