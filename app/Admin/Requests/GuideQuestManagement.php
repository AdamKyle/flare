<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class GuideQuestManagement extends FormRequest
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
            'name' => 'required',
            'intro_text' => 'required',
            'instructions' => 'required',
            'xp_reward' => 'required|integer',
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
            'name.required' => 'Missing Name',
            'intro_text.required' => 'Missing Intro Text',
            'instructions.required' => 'Missing Instructions',
            'xp_reward.required' => 'Missing XP Reward',
        ];
    }
}
