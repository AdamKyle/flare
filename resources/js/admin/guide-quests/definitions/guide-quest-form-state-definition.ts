import {
  GuideQuestBatchCraftedItemDefinition,
  GuideQuestContentBlockDefinition,
  GuideQuestWalkthroughStepDefinition,
} from '../api/definitions/guide-quest-definition';

export default interface GuideQuestFormStateDefinition {
  name: string;
  instructions: string;
  intro_text: GuideQuestContentBlockDefinition[];
  desktop_instructions: GuideQuestContentBlockDefinition[];
  mobile_instructions: GuideQuestContentBlockDefinition[];
  only_during_event: number | null;
  parent_id: number | null;
  unlock_at_level: string;
  required_level: string;
  required_exploration_hours: string;
  required_training_skill: number | null;
  required_training_xp_sacrifice: string;
  required_reincarnation_amount: string;
  required_skill: number | null;
  required_skill_level: string;
  required_secondary_skill: number | null;
  required_secondary_skill_level: string;
  required_skill_type: number | null;
  required_skill_type_level: string;
  required_faction_id: number | null;
  required_faction_level: string;
  required_fame_level: string;
  required_stats: string;
  required_str: string;
  required_dex: string;
  required_int: string;
  required_dur: string;
  required_chr: string;
  required_agi: string;
  required_focus: string;
  required_kingdoms: string;
  required_kingdom_level: string;
  required_kingdom_units: string;
  required_kingdom_building_id: string | null;
  required_kingdom_building_level: string;
  required_passive_skill: number | null;
  required_passive_level: string;
  required_specialty_type: string | null;
  required_holy_stacks: string;
  required_attached_gems: string;
  required_game_map_id: number | null;
  required_quest_id: number | null;
  required_quest_item_id: number | null;
  secondary_quest_item_id: number | null;
  be_on_game_map: number | null;
  required_event_goal_participation: string;
  required_event_goal_crafting_participation: string;
  required_event_goal_enchanting_participation: string;
  required_class_specials_equipped: string;
  required_class_rank_level: string;
  required_gold: string;
  required_gold_dust: string;
  required_shards: string;
  required_copper_coins: string;
  required_gold_bars: string;
  faction_points_per_kill: string;
  gold_dust_reward: string;
  shards_reward: string;
  gold_reward: string;
  xp_reward: string;
  must_be_pledged_to_faction: boolean;
  must_be_assisting_npc: boolean;
  required_delve_survival_time: string;
  required_delve_pack_size: string;
  required_batch_crafting_type: string | null;
  required_batch_crafting_hours: string;
  required_batch_crafted_items: GuideQuestBatchCraftedItemDefinition[];
  walkthrough_steps: GuideQuestWalkthroughStepDefinition[];
}
