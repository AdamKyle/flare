<?php

namespace App\Game\GuideQuests\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuideQuestWalkthroughProgressRequest extends FormRequest
{
    /**
     * Allow authenticated game middleware to authorize this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the walkthrough progress validation rules.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'step_id' => ['nullable', 'integer', 'exists:guide_quest_walkthrough_steps,id'],
            'completed' => ['required', 'boolean'],
        ];
    }
}
