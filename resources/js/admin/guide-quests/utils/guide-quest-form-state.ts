import { GUIDE_QUEST_FIELD_STEP } from './guide-quest-form-validation';
import GuideQuestDefinition from '../api/definitions/guide-quest-definition';
import GuideQuestFormErrorsDefinition, {
  GuideQuestBatchItemErrorDefinition,
} from '../definitions/guide-quest-form-errors-definition';
import GuideQuestFormStateDefinition from '../definitions/guide-quest-form-state-definition';

const numericValue = (value: number | null): string =>
  value === null ? '' : String(value);

const booleanValue = (value: boolean | number | null): boolean =>
  value === true || value === 1;

export const createGuideQuestFormState = (
  guideQuest: GuideQuestDefinition
): GuideQuestFormStateDefinition => ({
  name: guideQuest.name ?? '',
  instructions: guideQuest.instructions ?? '',
  intro_text: guideQuest.intro_text ?? [],
  desktop_instructions: guideQuest.desktop_instructions ?? [],
  mobile_instructions: guideQuest.mobile_instructions ?? [],
  only_during_event: guideQuest.only_during_event,
  parent_id: guideQuest.parent_id,
  unlock_at_level: numericValue(guideQuest.unlock_at_level),
  required_level: numericValue(guideQuest.required_level),
  required_exploration_hours: numericValue(
    guideQuest.required_exploration_hours
  ),
  required_training_skill: guideQuest.required_training_skill,
  required_training_xp_sacrifice: numericValue(
    guideQuest.required_training_xp_sacrifice
  ),
  required_reincarnation_amount: numericValue(
    guideQuest.required_reincarnation_amount
  ),
  required_skill: guideQuest.required_skill,
  required_skill_level: numericValue(guideQuest.required_skill_level),
  required_secondary_skill: guideQuest.required_secondary_skill,
  required_secondary_skill_level: numericValue(
    guideQuest.required_secondary_skill_level
  ),
  required_skill_type: guideQuest.required_skill_type,
  required_skill_type_level: numericValue(guideQuest.required_skill_type_level),
  required_faction_id: guideQuest.required_faction_id,
  required_faction_level: numericValue(guideQuest.required_faction_level),
  required_fame_level: numericValue(guideQuest.required_fame_level),
  required_stats: numericValue(guideQuest.required_stats),
  required_str: numericValue(guideQuest.required_str),
  required_dex: numericValue(guideQuest.required_dex),
  required_int: numericValue(guideQuest.required_int),
  required_dur: numericValue(guideQuest.required_dur),
  required_chr: numericValue(guideQuest.required_chr),
  required_agi: numericValue(guideQuest.required_agi),
  required_focus: numericValue(guideQuest.required_focus),
  required_kingdoms: numericValue(guideQuest.required_kingdoms),
  required_kingdom_level: numericValue(guideQuest.required_kingdom_level),
  required_kingdom_units: numericValue(guideQuest.required_kingdom_units),
  required_kingdom_building_id: guideQuest.required_kingdom_building_id,
  required_kingdom_building_level: numericValue(
    guideQuest.required_kingdom_building_level
  ),
  required_passive_skill: guideQuest.required_passive_skill,
  required_passive_level: numericValue(guideQuest.required_passive_level),
  required_specialty_type: guideQuest.required_specialty_type,
  required_holy_stacks: numericValue(guideQuest.required_holy_stacks),
  required_attached_gems: numericValue(guideQuest.required_attached_gems),
  required_game_map_id: guideQuest.required_game_map_id,
  required_quest_id: guideQuest.required_quest_id,
  required_quest_item_id: guideQuest.required_quest_item_id,
  secondary_quest_item_id: guideQuest.secondary_quest_item_id,
  be_on_game_map: guideQuest.be_on_game_map,
  required_event_goal_participation: numericValue(
    guideQuest.required_event_goal_participation
  ),
  required_event_goal_crafting_participation: numericValue(
    guideQuest.required_event_goal_crafting_participation
  ),
  required_event_goal_enchanting_participation: numericValue(
    guideQuest.required_event_goal_enchanting_participation
  ),
  required_class_specials_equipped: numericValue(
    guideQuest.required_class_specials_equipped
  ),
  required_class_rank_level: numericValue(guideQuest.required_class_rank_level),
  required_gold: numericValue(guideQuest.required_gold),
  required_gold_dust: numericValue(guideQuest.required_gold_dust),
  required_shards: numericValue(guideQuest.required_shards),
  required_copper_coins: numericValue(guideQuest.required_copper_coins),
  required_gold_bars: numericValue(guideQuest.required_gold_bars),
  faction_points_per_kill: numericValue(guideQuest.faction_points_per_kill),
  gold_dust_reward: numericValue(guideQuest.gold_dust_reward),
  shards_reward: numericValue(guideQuest.shards_reward),
  gold_reward: numericValue(guideQuest.gold_reward),
  xp_reward: numericValue(guideQuest.xp_reward),
  must_be_pledged_to_faction: booleanValue(
    guideQuest.must_be_pledged_to_faction
  ),
  must_be_assisting_npc: booleanValue(guideQuest.must_be_assisting_npc),
  required_delve_survival_time: numericValue(
    guideQuest.required_delve_survival_time
  ),
  required_delve_pack_size: numericValue(guideQuest.required_delve_pack_size),
  required_batch_crafting_type: guideQuest.required_batch_crafting_type,
  required_batch_crafting_hours: numericValue(
    guideQuest.required_batch_crafting_hours
  ),
  required_batch_crafted_items: guideQuest.required_batch_crafted_items ?? [],
  walkthrough_steps: guideQuest.walkthrough_steps ?? [],
});

export const createEmptyGuideQuestFormState =
  (): GuideQuestFormStateDefinition => ({
    name: '',
    instructions: '',
    intro_text: [],
    desktop_instructions: [],
    mobile_instructions: [],
    only_during_event: null,
    parent_id: null,
    unlock_at_level: '',
    required_level: '',
    required_exploration_hours: '',
    required_training_skill: null,
    required_training_xp_sacrifice: '',
    required_reincarnation_amount: '',
    required_skill: null,
    required_skill_level: '',
    required_secondary_skill: null,
    required_secondary_skill_level: '',
    required_skill_type: null,
    required_skill_type_level: '',
    required_faction_id: null,
    required_faction_level: '',
    required_fame_level: '',
    required_stats: '',
    required_str: '',
    required_dex: '',
    required_int: '',
    required_dur: '',
    required_chr: '',
    required_agi: '',
    required_focus: '',
    required_kingdoms: '',
    required_kingdom_level: '',
    required_kingdom_units: '',
    required_kingdom_building_id: null,
    required_kingdom_building_level: '',
    required_passive_skill: null,
    required_passive_level: '',
    required_specialty_type: null,
    required_holy_stacks: '',
    required_attached_gems: '',
    required_game_map_id: null,
    required_quest_id: null,
    required_quest_item_id: null,
    secondary_quest_item_id: null,
    be_on_game_map: null,
    required_event_goal_participation: '',
    required_event_goal_crafting_participation: '',
    required_event_goal_enchanting_participation: '',
    required_class_specials_equipped: '',
    required_class_rank_level: '',
    required_gold: '',
    required_gold_dust: '',
    required_shards: '',
    required_copper_coins: '',
    required_gold_bars: '',
    faction_points_per_kill: '',
    gold_dust_reward: '',
    shards_reward: '',
    gold_reward: '',
    xp_reward: '',
    must_be_pledged_to_faction: false,
    must_be_assisting_npc: false,
    required_delve_survival_time: '',
    required_delve_pack_size: '',
    required_batch_crafting_type: null,
    required_batch_crafting_hours: '',
    required_batch_crafted_items: [],
    walkthrough_steps: [],
  });

export const normalizeGuideQuestValidationErrors = (
  errors: Record<string, string>
): GuideQuestFormErrorsDefinition => {
  const normalized: GuideQuestFormErrorsDefinition = {};
  const rows: Record<number, GuideQuestBatchItemErrorDefinition> = {};

  Object.entries(errors).forEach(([rawField, message]) => {
    const field = rawField.startsWith('content.')
      ? rawField.slice('content.'.length)
      : rawField;
    const batchMatch = field.match(
      /^required_batch_crafted_items\.(\d+)\.(source|item_id|amount|must_be_enchanted)$/
    );

    if (batchMatch) {
      const rowIndex = Number(batchMatch[1]);
      const rowField =
        batchMatch[2] as keyof GuideQuestBatchItemErrorDefinition;
      rows[rowIndex] = { ...rows[rowIndex], [rowField]: message };
      normalized.required_batch_crafted_items = message;
      return;
    }

    if (field in GUIDE_QUEST_FIELD_STEP) {
      normalized[field as keyof GuideQuestFormStateDefinition] = message;
    }
  });

  if (Object.keys(rows).length > 0) {
    normalized.required_batch_crafted_items_rows = rows;
  }

  return normalized;
};
