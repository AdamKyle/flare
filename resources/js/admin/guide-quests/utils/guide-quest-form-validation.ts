import GuideQuestFormErrorsDefinition from '../definitions/guide-quest-form-errors-definition';
import GuideQuestFormStateDefinition from '../definitions/guide-quest-form-state-definition';

export const GUIDE_QUEST_FIELD_STEP: Partial<
  Record<keyof GuideQuestFormStateDefinition, number>
> = {
  name: 0,
  instructions: 0,
  only_during_event: 0,
  parent_id: 0,
  unlock_at_level: 0,
  intro_text: 1,
  desktop_instructions: 2,
  mobile_instructions: 3,
  walkthrough_steps: 4,
  required_level: 5,
  required_exploration_hours: 5,
  required_training_skill: 5,
  required_training_xp_sacrifice: 5,
  required_reincarnation_amount: 5,
  required_skill: 5,
  required_skill_level: 5,
  required_secondary_skill: 5,
  required_secondary_skill_level: 5,
  required_skill_type: 5,
  required_skill_type_level: 5,
  required_delve_survival_time: 5,
  required_delve_pack_size: 5,
  required_batch_crafting_type: 5,
  required_batch_crafting_hours: 5,
  required_batch_crafted_items: 5,
  required_faction_id: 6,
  required_faction_level: 6,
  required_fame_level: 6,
  must_be_pledged_to_faction: 6,
  must_be_assisting_npc: 6,
  required_stats: 7,
  required_str: 7,
  required_dex: 7,
  required_int: 7,
  required_dur: 7,
  required_chr: 7,
  required_agi: 7,
  required_focus: 7,
  required_kingdoms: 8,
  required_kingdom_level: 8,
  required_kingdom_units: 8,
  required_kingdom_building_id: 8,
  required_kingdom_building_level: 8,
  required_passive_skill: 8,
  required_passive_level: 8,
  required_specialty_type: 9,
  required_holy_stacks: 9,
  required_attached_gems: 9,
  required_game_map_id: 10,
  required_quest_id: 10,
  required_quest_item_id: 10,
  secondary_quest_item_id: 10,
  be_on_game_map: 10,
  required_event_goal_participation: 10,
  required_event_goal_crafting_participation: 10,
  required_event_goal_enchanting_participation: 10,
  required_class_specials_equipped: 11,
  required_class_rank_level: 11,
  required_gold: 12,
  required_gold_dust: 12,
  required_shards: 12,
  required_copper_coins: 12,
  required_gold_bars: 12,
  faction_points_per_kill: 13,
  gold_dust_reward: 13,
  shards_reward: 13,
  gold_reward: 13,
  xp_reward: 13,
};

const validateTraining = (
  state: GuideQuestFormStateDefinition
): GuideQuestFormErrorsDefinition => {
  const errors: GuideQuestFormErrorsDefinition = {};
  const value = state.required_training_xp_sacrifice;

  if (value !== '' && Number.isNaN(Number(value))) {
    errors.required_training_xp_sacrifice =
      'Training XP Sacrifice must be a number.';
  } else if (value !== '' && Number(value) < 0) {
    errors.required_training_xp_sacrifice =
      'Training XP Sacrifice cannot be below 0.';
  } else if (value !== '' && Number(value) > 1) {
    errors.required_training_xp_sacrifice =
      'Training XP Sacrifice cannot be above 1.';
  }

  state.required_batch_crafted_items.forEach((item, index) => {
    const rowErrors: Record<string, string> = {};

    if (item.item_id === null) {
      rowErrors.item_id = 'A required crafted item must be selected.';
    }

    if (!Number.isInteger(Number(item.amount)) || Number(item.amount) < 1) {
      rowErrors.amount = 'Required crafted item amount must be at least 1.';
    }

    if (item.source !== 'inventory' && item.source !== 'alchemy_bag') {
      rowErrors.source = 'Required crafted item source is invalid.';
    }

    if (Object.keys(rowErrors).length > 0) {
      errors.required_batch_crafted_items =
        Object.values(rowErrors)[0] ?? 'Required crafted item is invalid.';
      errors.required_batch_crafted_items_rows = {
        ...errors.required_batch_crafted_items_rows,
        [index]: rowErrors,
      };
    }
  });

  return errors;
};

export const validateGuideQuestStep = (
  stepIndex: number,
  state: GuideQuestFormStateDefinition
): GuideQuestFormErrorsDefinition => {
  if (stepIndex === 0 && state.name.trim() === '') {
    return { name: 'Guide Quest Name is required.' };
  }

  if (stepIndex === 5) {
    return validateTraining(state);
  }

  return {};
};

export const validateGuideQuestForm = (
  state: GuideQuestFormStateDefinition
): GuideQuestFormErrorsDefinition => {
  const errors: GuideQuestFormErrorsDefinition = {};

  for (let stepIndex = 0; stepIndex < 14; stepIndex += 1) {
    Object.assign(errors, validateGuideQuestStep(stepIndex, state));
  }

  return errors;
};

export const firstGuideQuestErrorStep = (
  errors: GuideQuestFormErrorsDefinition
): number | null => {
  for (const field of Object.keys(errors) as Array<
    keyof GuideQuestFormStateDefinition
  >) {
    const step = GUIDE_QUEST_FIELD_STEP[field];

    if (step !== undefined) {
      return step;
    }
  }

  return null;
};
