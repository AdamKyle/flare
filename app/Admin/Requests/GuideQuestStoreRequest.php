<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuideQuestStoreRequest extends FormRequest
{
    private const CONTENT_FIELDS = [
        'name', 'intro_text', 'instructions', 'desktop_instructions', 'mobile_instructions',
        'required_level', 'required_exploration_hours', 'required_training_skill',
        'required_training_xp_sacrifice', 'required_reincarnation_amount', 'required_skill',
        'required_skill_level', 'required_secondary_skill', 'required_secondary_skill_level',
        'required_faction_id', 'required_faction_level', 'required_game_map_id', 'required_quest_id',
        'required_quest_item_id', 'secondary_quest_item_id', 'required_kingdoms',
        'required_kingdom_level', 'required_kingdom_units', 'required_kingdom_building_id',
        'required_kingdom_building_level', 'required_passive_skill', 'required_passive_level',
        'required_skill_type', 'required_skill_type_level', 'required_class_specials_equipped',
        'required_class_rank_level', 'required_gold', 'required_gold_dust', 'required_shards',
        'required_copper_coins', 'required_gold_bars', 'required_stats', 'required_str', 'required_dex',
        'required_int', 'required_dur', 'required_chr', 'required_agi', 'required_focus',
        'faction_points_per_kill', 'gold_dust_reward', 'shards_reward', 'gold_reward', 'xp_reward',
        'parent_id', 'unlock_at_level', 'only_during_event', 'be_on_game_map',
        'required_event_goal_participation', 'required_event_goal_crafting_participation',
        'required_event_goal_enchanting_participation', 'required_holy_stacks',
        'required_attached_gems', 'required_specialty_type', 'must_be_pledged_to_faction',
        'must_be_assisting_npc', 'required_fame_level', 'required_delve_survival_time',
        'required_delve_pack_size', 'required_batch_crafting_type', 'required_batch_crafting_hours',
        'required_batch_crafted_items',
    ];

    /**
     * Normalize empty multipart content collections before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $content = $this->input('content');

        if (! is_array($content)) {
            return;
        }

        foreach (['intro_text', 'desktop_instructions', 'mobile_instructions'] as $field) {
            if (($content[$field] ?? null) === '') {
                $content[$field] = [];
            }
        }

        $this->merge(['content' => $content]);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'guide_quest_id' => 'required|integer',
            'has_image' => 'required|boolean',
            'content' => ['required', 'array:'.implode(',', self::CONTENT_FIELDS)],
            'content.name' => 'required|string|max:255',
            'content.instructions' => 'sometimes|nullable|string',
            'content.intro_text' => 'sometimes|array',
            'content.desktop_instructions' => 'sometimes|array',
            'content.mobile_instructions' => 'sometimes|array',
            'content.required_training_xp_sacrifice' => 'sometimes|nullable|numeric|min:0|max:1',
            'content.required_batch_crafting_type' => 'sometimes|nullable|in:craft,craft_and_enchant,alchemy,trinketry',
            'content.required_batch_crafted_items' => 'sometimes|nullable|array|max:2',
            'content.required_batch_crafted_items.*' => 'array:source,item_id,amount,must_be_enchanted',
            'content.required_batch_crafted_items.*.source' => 'required|in:inventory,alchemy_bag',
            'content.required_batch_crafted_items.*.item_id' => 'required|integer|exists:items,id',
            'content.required_batch_crafted_items.*.amount' => 'required|integer|min:1',
            'content.required_batch_crafted_items.*.must_be_enchanted' => 'required|boolean',
            'content.required_specialty_type' => 'sometimes|nullable|string',
            'content.must_be_pledged_to_faction' => 'sometimes|nullable|boolean',
            'content.must_be_assisting_npc' => 'sometimes|nullable|boolean',
        ];

        foreach (self::CONTENT_FIELDS as $field) {
            if (array_key_exists('content.'.$field, $rules)) {
                continue;
            }

            $rules['content.'.$field] = 'sometimes|nullable|integer';
        }

        foreach (['intro_text', 'desktop_instructions', 'mobile_instructions'] as $contentField) {
            $rules['content.'.$contentField.'.*'] = 'array:id,content,image_url';
            $rules['content.'.$contentField.'.*.id'] = 'nullable|string|max:255';
            $rules['content.'.$contentField.'.*.content'] = 'required|string';
            $rules['content.'.$contentField.'.*.image_url'] = 'nullable';
        }

        return $rules;
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'guide_quest_id' => 'What guide quest do you want to manage?',
            'content.name.required' => 'Guide Quest Name is required.',
            'content.required_training_xp_sacrifice.numeric' => 'Training XP Sacrifice must be a number.',
            'content.required_training_xp_sacrifice.min' => 'Training XP Sacrifice cannot be below 0.',
            'content.required_training_xp_sacrifice.max' => 'Training XP Sacrifice cannot be above 1.',
            'content.required_batch_crafted_items.*.amount.min' => 'Required crafted item amount must be at least 1.',
        ];
    }
}
