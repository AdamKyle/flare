<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class QuestManagement extends FormRequest
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
            'name'                          => 'required',
            'npc_id'                        => 'required',
            'before_completion_description' => 'required',
            'after_completion_description'  => 'required',
            'item_id'                       => 'nullable',
            'gold_dust_cost'                => 'nullable',
            'shard_cost'                    => 'nullable',
            'gold_cost'                     => 'nullable',
            'copper_coin_cost'              => 'nullable',
            'reward_item'                   => 'nullable',
            'reward_gold_dust'              => 'nullable',
            'reward_shards'                 => 'nullable',
            'reward_gold'                   => 'nullable',
            'reward_xp'                     => 'nullable',
            'unlocks_skill'                 => 'nullable',
            'unlocks_skill_type'            => 'nullable',
            'is_parent'                     => 'nullable',
            'parent_quest_id'               => 'nullable',
            'faction_game_map_id'           => 'nullable',
            'secondary_required_item'       => 'nullable',
            'required_faction_level'        => 'nullable',
            'access_to_map_id'              => 'nullable',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'name.required'                          => 'Missing Quest name',
            'npc_id.required'                        => 'Missing NPC',
            'before_completion_description.required' => 'Missing Before Completion Text',
            'after_completion_description.required'  => 'Missing After Completion text',
        ];
    }
}
