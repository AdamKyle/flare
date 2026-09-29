<?php

namespace App\Admin\Requests;

use App\Game\GuideQuests\Values\GuideQuestWalkthroughEvent;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughStepType;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuideQuestWalkthroughRequest extends FormRequest
{
    /**
     * Allow the Admin middleware to authorize this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return validation rules for replacing ordered walkthrough steps.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'steps' => ['present', 'array'],
            'steps.*.title' => ['required', 'string', 'max:255'],
            'steps.*.content' => ['required', 'string'],
            'steps.*.step_type' => ['required', Rule::enum(GuideQuestWalkthroughStepType::class)],
            'steps.*.target' => ['nullable', Rule::enum(GuideQuestWalkthroughTarget::class)],
            'steps.*.required_event' => ['nullable', Rule::enum(GuideQuestWalkthroughEvent::class)],
            'steps.*.optional_if_target_missing' => ['boolean'],
        ];
    }
}
