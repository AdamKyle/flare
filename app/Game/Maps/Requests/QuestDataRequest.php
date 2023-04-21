<?php

namespace App\Game\Maps\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuestDataRequest extends FormRequest
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
            'completed_quests_only' => 'required|boolean',
        ];
    }

    /**
     * Get messages for the validation failure.
     * 
     * @return array
     */
    public function messages() {
        return [
            'completed_quests_only.required' => 'Missing: Completed Quests Only',
        ];
    }
}
