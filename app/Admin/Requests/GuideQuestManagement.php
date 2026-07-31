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
            'required_batch_crafting_type' => 'nullable|required_with:required_batch_crafting_hours|in:craft,craft_and_enchant,alchemy,trinketry',
            'required_batch_crafting_hours' => 'nullable|required_with:required_batch_crafting_type|integer|min:1',
            'required_batch_crafted_items' => 'nullable|array|max:2',
            'required_batch_crafted_items.*.source' => 'nullable|in:inventory,alchemy_bag',
            'required_batch_crafted_items.*.item_id' => 'nullable|integer|exists:items,id',
            'required_batch_crafted_items.*.amount' => 'nullable|integer|min:1',
            'required_batch_crafted_items.*.must_be_enchanted' => 'nullable|boolean',
            'required_event_goal_crafting_participation' => 'nullable|integer|min:1',
            'required_event_goal_enchanting_participation' => 'nullable|integer|min:1',
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
