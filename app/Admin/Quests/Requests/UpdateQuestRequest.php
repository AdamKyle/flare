<?php

namespace App\Admin\Quests\Requests;

use App\Admin\Quests\Requests\Concerns\ValidatesQuestGraph;
use App\Flare\Models\Quest;
use App\Game\Core\Values\FeatureType;
use App\Game\Events\Values\EventType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateQuestRequest extends FormRequest
{
    use ValidatesQuestGraph;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always true; authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'npc_id' => 'required|integer|exists:npcs,id',
            'raid_id' => 'nullable|integer|exists:raids,id',
            'only_for_event' => ['nullable', 'integer', Rule::in(array_keys(EventType::getOptionsForSelect()))],
            'before_completion_description' => 'nullable|string',
            'after_completion_description' => 'nullable|string',

            'parent_quest_id' => 'nullable|integer|exists:quests,id',
            'required_quest_id' => 'nullable|integer|exists:quests,id',
            'required_quest_chain' => 'nullable|array',
            'required_quest_chain.*' => 'integer',
            'reincarnated_times' => 'nullable|integer|min:0',

            'item_id' => 'nullable|integer|exists:items,id',
            'secondary_required_item' => 'nullable|integer|exists:items,id',
            'access_to_map_id' => 'nullable|integer|exists:game_maps,id',
            'faction_game_map_id' => 'nullable|integer|exists:game_maps,id',
            'required_faction_level' => 'nullable|integer|min:0',
            'assisting_npc_id' => 'nullable|integer|exists:npcs,id',
            'required_fame_level' => 'nullable|integer|min:0',
            'gold_cost' => 'nullable|integer|min:0',
            'gold_dust_cost' => 'nullable|integer|min:0',
            'shard_cost' => 'nullable|integer|min:0',
            'copper_coin_cost' => 'nullable|integer|min:0',

            'reward_item' => 'nullable|integer|exists:items,id',
            'reward_gold' => 'nullable|integer|min:0',
            'reward_gold_dust' => 'nullable|integer|min:0',
            'reward_shards' => 'nullable|integer|min:0',
            'reward_xp' => 'nullable|integer|min:0',
            'unlocks_skill' => 'required|boolean',
            'unlocks_skill_type' => ['nullable', 'integer', Rule::enum(SkillTypeValue::class)],
            'unlocks_feature' => ['nullable', 'integer', Rule::enum(FeatureType::class)],
            'unlocks_passive_id' => 'nullable|integer|exists:passive_skills,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Quest name.',
            'only_for_event.in' => 'The selected event restriction is invalid.',
            'unlocks_skill_type.enum' => 'The selected skill type is invalid.',
            'unlocks_feature.enum' => 'The selected feature is invalid.',
        ];
    }

    /**
     * Normalize present boolean fields before validation.
     *
     * @return void Merges normalized boolean values into the request input.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('unlocks_skill')) {
            $this->merge(['unlocks_skill' => $this->boolean('unlocks_skill')]);
        }
    }

    /**
     * Configure the validator instance to enforce Quest graph integrity against the Quest being updated.
     *
     * @param  Validator  $validator  Validator instance to configure.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $quest = $this->route('quest');
            $excludeId = $quest instanceof Quest ? $quest->id : null;

            $this->validateParentCycle($validator, $this->input('parent_quest_id'), $excludeId);
            $this->validateRequiredQuestCycle($validator, $this->input('required_quest_id'), $excludeId);
            $this->validateRequiredQuestChain($validator, $this->input('required_quest_chain'), $excludeId);
        });
    }
}
