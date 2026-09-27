<?php

namespace App\Admin\Transformers;

use App\Flare\Models\GuideQuest;

class GuideQuestDetailTransformer
{
    /**
     * Transform a Guide Quest into its read-only Admin detail representation.
     *
     * @param GuideQuest $guideQuest
     * @return array
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
            'required_reincarnation_amount' => $guideQuest->required_reincarnation_amount,
            'unlock_at_level' => $guideQuest->unlock_at_level,
            'required_skill' => $guideQuest->required_skill,
            'skill_name' => $guideQuest->skill_name,
            'required_skill_level' => $guideQuest->required_skill_level,
            'required_secondary_skill' => $guideQuest->required_secondary_skill,
            'secondary_skill_name' => $guideQuest->secondary_skill_name,
            'required_secondary_skill_level' => $guideQuest->required_secondary_skill_level,
            'required_skill_type' => $guideQuest->required_skill_type,
            'skill_type_name' => $guideQuest->skill_type_name,
            'required_skill_type_level' => $guideQuest->required_skill_type_level,
            'required_passive_skill' => $guideQuest->required_passive_skill,
            'passive_name' => $guideQuest->passive_name,
            'required_passive_level' => $guideQuest->required_passive_level,
            'required_class_specials_equipped' => $guideQuest->required_class_specials_equipped,
            'required_class_rank_level' => $guideQuest->required_class_rank_level,
            'required_specialty_type' => $guideQuest->required_specialty_type,
            'required_fame_level' => $guideQuest->required_fame_level,
            'required_faction_id' => $guideQuest->required_faction_id,
            'faction_name' => $guideQuest->faction_name,
            'required_faction_level' => $guideQuest->required_faction_level,
            'must_be_pledged_to_faction' => $guideQuest->must_be_pledged_to_faction,
            'must_be_assisting_npc' => $guideQuest->must_be_assisting_npc,
            'required_game_map_id' => $guideQuest->required_game_map_id,
            'game_map_name' => $guideQuest->game_map_name,
            'be_on_game_map' => $guideQuest->be_on_game_map,
            'required_to_be_on_game_map_name' => $guideQuest->required_to_be_on_game_map_name,
            'required_quest_id' => $guideQuest->required_quest_id,
            'quest_name' => $guideQuest->quest_name,
            'parent_id' => $guideQuest->parent_id,
            'parent_quest_name' => $guideQuest->parent_quest_name,
            'required_quest_item_id' => $guideQuest->required_quest_item_id,
            'quest_item_name' => $guideQuest->quest_item_name,
            'secondary_quest_item_id' => $guideQuest->secondary_quest_item_id,
            'secondary_quest_item_name' => $guideQuest->secondary_quest_item_name,
            'only_during_event' => $guideQuest->only_during_event,
            'required_event_goal_participation' => $guideQuest->required_event_goal_participation,
            'required_event_goal_crafting_participation' => $guideQuest->required_event_goal_crafting_participation,
            'required_event_goal_enchanting_participation' => $guideQuest->required_event_goal_enchanting_participation,
            'required_kingdoms' => $guideQuest->required_kingdoms,
            'required_kingdom_level' => $guideQuest->required_kingdom_level,
            'required_kingdom_units' => $guideQuest->required_kingdom_units,
            'required_kingdom_building_id' => $guideQuest->required_kingdom_building_id,
            'kingdom_building_name' => $guideQuest->kingdom_building_name,
            'required_kingdom_building_level' => $guideQuest->required_kingdom_building_level,
            'required_gold_bars' => $guideQuest->required_gold_bars,
            'required_stats' => $guideQuest->required_stats,
            'required_str' => $guideQuest->required_str,
            'required_dex' => $guideQuest->required_dex,
            'required_int' => $guideQuest->required_int,
            'required_dur' => $guideQuest->required_dur,
            'required_chr' => $guideQuest->required_chr,
            'required_agi' => $guideQuest->required_agi,
            'required_focus' => $guideQuest->required_focus,
            'required_gold' => $guideQuest->required_gold,
            'required_gold_dust' => $guideQuest->required_gold_dust,
            'required_shards' => $guideQuest->required_shards,
            'required_copper_coins' => $guideQuest->required_copper_coins,
            'required_holy_stacks' => $guideQuest->required_holy_stacks,
            'required_attached_gems' => $guideQuest->required_attached_gems,
            'required_delve_survival_time' => $guideQuest->required_delve_survival_time,
            'required_delve_pack_size' => $guideQuest->required_delve_pack_size,
            'required_batch_crafting_type' => $guideQuest->required_batch_crafting_type,
            'required_batch_crafting_type_name' => $guideQuest->required_batch_crafting_type_name,
            'required_batch_crafting_hours' => $guideQuest->required_batch_crafting_hours,
            'required_batch_crafted_item_names' => $guideQuest->required_batch_crafted_item_names,
            'xp_reward' => $guideQuest->xp_reward,
            'gold_reward' => $guideQuest->gold_reward,
            'gold_dust_reward' => $guideQuest->gold_dust_reward,
            'shards_reward' => $guideQuest->shards_reward,
            'faction_points_per_kill' => $guideQuest->faction_points_per_kill,
        ];
    }
}
