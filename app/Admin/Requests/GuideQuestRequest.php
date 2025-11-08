<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class GuideQuestRequest extends FormRequest
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
            'guide_quest_id' => 'required|integer',
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
            'guide_quest_id' => 'What guide quest do you want to manage?',
        ];
    }
}
