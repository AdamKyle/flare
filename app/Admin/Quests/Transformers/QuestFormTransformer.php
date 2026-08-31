<?php

namespace App\Admin\Quests\Transformers;

use App\Flare\Models\Quest;

class QuestFormTransformer
{
    /**
     * Transform a Quest into its Admin save-response / form-value representation.
     *
     * Returns every current field managed by the modern Quest form.
     * `is_parent` and `parent_chain_quest_id` are intentionally excluded; they
     * are legacy/compatibility state maintained internally, not form fields.
     *
     * @param  Quest  $quest  Quest to transform.
     * @return array<string, mixed> Admin Quest form-value representation.
     */
    public function transform(Quest $quest): array
    {
        return [
            'id' => $quest->id,
            'name' => $quest->name,
            'npc_id' => $quest->npc_id,
            'raid_id' => $quest->raid_id,
            'only_for_event' => $quest->only_for_event,
            'before_completion_description' => $quest->before_completion_description,
            'after_completion_description' => $quest->after_completion_description,

            'parent_quest_id' => $quest->parent_quest_id,
            'required_quest_id' => $quest->required_quest_id,
            'required_quest_chain' => $quest->required_quest_chain ?? [],
            'reincarnated_times' => $quest->reincarnated_times,

            'item_id' => $quest->item_id,
            'secondary_required_item' => $quest->secondary_required_item,
            'access_to_map_id' => $quest->access_to_map_id,
            'faction_game_map_id' => $quest->faction_game_map_id,
            'required_faction_level' => $quest->required_faction_level,
            'assisting_npc_id' => $quest->assisting_npc_id,
            'required_fame_level' => $quest->required_fame_level,
            'gold_cost' => $quest->gold_cost,
            'gold_dust_cost' => $quest->gold_dust_cost,
            'shard_cost' => $quest->shard_cost,
            'copper_coin_cost' => $quest->copper_coin_cost,

            'reward_item' => $quest->reward_item,
            'reward_gold' => $quest->reward_gold,
            'reward_gold_dust' => $quest->reward_gold_dust,
            'reward_shards' => $quest->reward_shards,
            'reward_xp' => $quest->reward_xp,
            'unlocks_skill' => $quest->unlocks_skill,
            'unlocks_skill_type' => $quest->unlocks_skill_type,
            'unlocks_feature' => $quest->unlocks_feature,
            'unlocks_passive_id' => $quest->unlocks_passive_id,
        ];
    }
}
