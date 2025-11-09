<?php

namespace App\Admin\Transformers;

use App\Flare\Models\GuideQuest;
use App\Flare\Transformers\BaseTransformer;

class GuideQuestTransformer extends BaseTransformer
{
    /**
     * Transform a guide quest into a response array.
     */
    public function transform(GuideQuest $guideQuest): array
    {
        return [
            'id' => $guideQuest->id,
            'name' => $guideQuest->name,
            'intro_text' => $guideQuest->intro_text,
            'desktop_instructions' => $guideQuest->desktop_instructions,
            'mobile_instructions' => $guideQuest->mobile_instructions,
            'required_level' => $guideQuest->required_level,
            'required_skill' => $guideQuest->required_skill,
            'required_skill_level' => $guideQuest->required_skill_level,
            'required_faction_id' => $guideQuest->required_faction_id,
            'required_faction_level' => $guideQuest->required_faction_level,
            'required_game_map_id' => $guideQuest->required_game_map_id,
            'required_quest_id' => $guideQuest->required_quest_id,
            'required_quest_item_id' => $guideQuest->required_quest_item_id,
            'created_at' => $guideQuest->created_at,
            'updated_at' => $guideQuest->updated_at,
            'gold_dust_reward' => $guideQuest->gold_dust_reward,
            'shards_reward' => $guideQuest->shards_reward,
            'required_kingdoms' => $guideQuest->required_kingdoms,
            'required_kingdom_level' => $guideQuest->required_kingdom_level,
            'required_kingdom_units' => $guideQuest->required_kingdom_units,
            'required_passive_skill' => $guideQuest->required_passive_skill,
            'required_passive_level' => $guideQuest->required_passive_level,
            'faction_points_per_kill' => $guideQuest->faction_points_per_kill,
            'required_shards' => $guideQuest->required_shards,
            'xp_reward' => $guideQuest->xp_reward,
            'gold_reward' => $guideQuest->gold_reward,
            'required_gold_dust' => $guideQuest->required_gold_dust,
            'required_gold' => $guideQuest->required_gold,
            'required_stats' => $guideQuest->required_stats,
            'required_str' => $guideQuest->required_str,
            'required_dex' => $guideQuest->required_dex,
            'required_int' => $guideQuest->required_int,
            'required_dur' => $guideQuest->required_dur,
            'required_chr' => $guideQuest->required_chr,
            'required_agi' => $guideQuest->required_agi,
            'required_focus' => $guideQuest->required_focus,
            'required_secondary_skill' => $guideQuest->required_secondary_skill,
            'required_secondary_skill_level' => $guideQuest->required_secondary_skill_level,
            'secondary_quest_item_id' => $guideQuest->secondary_quest_item_id,
            'required_skill_type' => $guideQuest->required_skill_type,
            'required_skill_type_level' => $guideQuest->required_skill_type_level,
            'required_class_specials_equipped' => $guideQuest->required_class_specials_equipped,
            'required_class_rank_level' => $guideQuest->required_class_rank_level,
            'required_kingdom_building_id' => $guideQuest->required_kingdom_building_id,
            'required_kingdom_building_level' => $guideQuest->required_kingdom_building_level,
            'required_gold_bars' => $guideQuest->required_gold_bars,
            'parent_id' => $guideQuest->parent_id,
            'unlock_at_level' => $guideQuest->unlock_at_level,
            'only_during_event' => $guideQuest->only_during_event,
            'be_on_game_map' => $guideQuest->be_on_game_map,
            'required_event_goal_participation' => $guideQuest->required_event_goal_participation,
            'required_holy_stacks' => $guideQuest->required_holy_stacks,
            'required_attached_gems' => $guideQuest->required_attached_gems,
            'required_copper_coins' => $guideQuest->required_copper_coins,
            'required_specialty_type' => $guideQuest->required_specialty_type,
            'required_fame_level' => $guideQuest->required_fame_level,
        ];
    }
}
