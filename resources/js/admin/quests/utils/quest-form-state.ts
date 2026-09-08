import QuestFormDefinition from '../api/definitions/quest-form-definition';
import QuestFormStateDefinition from '../definitions/quest-form-state-definition';

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

export const createQuestFormState = (
  quest: QuestFormDefinition | null
): QuestFormStateDefinition => {
  if (!quest) {
    return {
      name: '',
      npc_id: null,
      raid_id: null,
      only_for_event: null,
      before_completion_description: '',
      after_completion_description: '',
      parent_quest_id: null,
      required_quest_id: null,
      required_quest_chain: [],
      reincarnated_times: '',
      item_id: null,
      secondary_required_item: null,
      access_to_map_id: null,
      faction_game_map_id: null,
      required_faction_level: '',
      assisting_npc_id: null,
      required_fame_level: '',
      gold_cost: '',
      gold_dust_cost: '',
      shard_cost: '',
      copper_coin_cost: '',
      reward_item: null,
      reward_gold: '',
      reward_gold_dust: '',
      reward_shards: '',
      reward_xp: '',
      unlocks_skill: false,
      unlocks_skill_type: null,
      unlocks_feature: null,
      unlocks_passive_id: null,
    };
  }

  return {
    name: quest.name,
    npc_id: quest.npc_id,
    raid_id: quest.raid_id,
    only_for_event: quest.only_for_event,
    before_completion_description: quest.before_completion_description ?? '',
    after_completion_description: quest.after_completion_description ?? '',
    parent_quest_id: quest.parent_quest_id,
    required_quest_id: quest.required_quest_id,
    required_quest_chain: quest.required_quest_chain,
    reincarnated_times: toStringValue(quest.reincarnated_times),
    item_id: quest.item_id,
    secondary_required_item: quest.secondary_required_item,
    access_to_map_id: quest.access_to_map_id,
    faction_game_map_id: quest.faction_game_map_id,
    required_faction_level: toStringValue(quest.required_faction_level),
    assisting_npc_id: quest.assisting_npc_id,
    required_fame_level: toStringValue(quest.required_fame_level),
    gold_cost: toStringValue(quest.gold_cost),
    gold_dust_cost: toStringValue(quest.gold_dust_cost),
    shard_cost: toStringValue(quest.shard_cost),
    copper_coin_cost: toStringValue(quest.copper_coin_cost),
    reward_item: quest.reward_item,
    reward_gold: toStringValue(quest.reward_gold),
    reward_gold_dust: toStringValue(quest.reward_gold_dust),
    reward_shards: toStringValue(quest.reward_shards),
    reward_xp: toStringValue(quest.reward_xp),
    unlocks_skill: quest.unlocks_skill,
    unlocks_skill_type: quest.unlocks_skill_type,
    unlocks_feature: quest.unlocks_feature,
    unlocks_passive_id: quest.unlocks_passive_id,
  };
};

export const buildQuestRequestPayload = (
  state: QuestFormStateDefinition
): Record<string, unknown> => ({
  name: state.name,
  npc_id: state.npc_id,
  raid_id: state.raid_id,
  only_for_event: state.only_for_event,
  before_completion_description: state.before_completion_description || null,
  after_completion_description: state.after_completion_description || null,
  parent_quest_id: state.parent_quest_id,
  required_quest_id: state.required_quest_id,
  required_quest_chain: state.required_quest_chain,
  reincarnated_times: toNumberOrNull(state.reincarnated_times),
  item_id: state.item_id,
  secondary_required_item: state.secondary_required_item,
  access_to_map_id: state.access_to_map_id,
  faction_game_map_id: state.faction_game_map_id,
  required_faction_level: toNumberOrNull(state.required_faction_level),
  assisting_npc_id: state.assisting_npc_id,
  required_fame_level: toNumberOrNull(state.required_fame_level),
  gold_cost: toNumberOrNull(state.gold_cost),
  gold_dust_cost: toNumberOrNull(state.gold_dust_cost),
  shard_cost: toNumberOrNull(state.shard_cost),
  copper_coin_cost: toNumberOrNull(state.copper_coin_cost),
  reward_item: state.reward_item,
  reward_gold: toNumberOrNull(state.reward_gold),
  reward_gold_dust: toNumberOrNull(state.reward_gold_dust),
  reward_shards: toNumberOrNull(state.reward_shards),
  reward_xp: toNumberOrNull(state.reward_xp),
  unlocks_skill: state.unlocks_skill,
  unlocks_skill_type: state.unlocks_skill_type,
  unlocks_feature: state.unlocks_feature,
  unlocks_passive_id: state.unlocks_passive_id,
});

type QuestFormErrors = Partial<Record<keyof QuestFormStateDefinition, string>>;

type QuestNumericStringField =
  | 'reincarnated_times'
  | 'required_faction_level'
  | 'required_fame_level'
  | 'gold_cost'
  | 'gold_dust_cost'
  | 'shard_cost'
  | 'copper_coin_cost'
  | 'reward_gold'
  | 'reward_gold_dust'
  | 'reward_shards'
  | 'reward_xp';

const NON_NEGATIVE_INTEGER_MESSAGE = 'Enter a whole number of 0 or more.';

const isValidNonNegativeIntegerString = (value: string): boolean => {
  if (value.trim() === '') {
    return true;
  }

  const parsed = Number(value);

  return Number.isInteger(parsed) && parsed >= 0;
};

const validateNonNegativeIntegerFields = (
  state: QuestFormStateDefinition,
  fields: ReadonlyArray<QuestNumericStringField>
): QuestFormErrors => {
  const errors: QuestFormErrors = {};

  fields.forEach((field) => {
    if (!isValidNonNegativeIntegerString(state[field])) {
      errors[field] = NON_NEGATIVE_INTEGER_MESSAGE;
    }
  });

  return errors;
};

export const validateQuestStoryStep = (
  state: QuestFormStateDefinition
): QuestFormErrors => {
  const errors: QuestFormErrors = {};

  if (state.name.trim() === '') {
    errors.name = 'Enter a Quest name.';
  }

  if (state.npc_id === null) {
    errors.npc_id = 'Select a Quest Giver NPC.';
  }

  return errors;
};

export const validateQuestStructureStep = (
  state: QuestFormStateDefinition
): QuestFormErrors =>
  validateNonNegativeIntegerFields(state, ['reincarnated_times']);

export const validateQuestRequirementsStep = (
  state: QuestFormStateDefinition
): QuestFormErrors =>
  validateNonNegativeIntegerFields(state, [
    'required_faction_level',
    'required_fame_level',
    'gold_cost',
    'gold_dust_cost',
    'shard_cost',
    'copper_coin_cost',
  ]);

export const validateQuestRewardsStep = (
  state: QuestFormStateDefinition
): QuestFormErrors =>
  validateNonNegativeIntegerFields(state, [
    'reward_gold',
    'reward_gold_dust',
    'reward_shards',
    'reward_xp',
  ]);

const QUEST_FORM_STEP_VALIDATORS: ReadonlyArray<
  (state: QuestFormStateDefinition) => QuestFormErrors
> = [
  validateQuestStoryStep,
  validateQuestStructureStep,
  validateQuestRequirementsStep,
  validateQuestRewardsStep,
];

export const validateQuestForm = (
  state: QuestFormStateDefinition
): QuestFormErrors => {
  const errors: QuestFormErrors = {};

  QUEST_FORM_STEP_VALIDATORS.forEach((validateStep) => {
    Object.assign(errors, validateStep(state));
  });

  return errors;
};
