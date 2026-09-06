import { isGemType } from '../../shared/enums/gem-type';
import LocationGemFormDefinition from '../api/definitions/location-gem-form-definition';
import LocationGemRequestDefinition from '../api/definitions/location-gem-request-definition';
import LocationGemFormErrorsDefinition from '../definitions/location-gem-form-errors-definition';
import LocationGemFormStateDefinition from '../definitions/location-gem-form-state-definition';
import LocationGemValidationResultDefinition from '../definitions/location-gem-validation-result-definition';

type LocationGemRangeField =
  | 'character_xp_bonus_range'
  | 'character_class_rank_xp_bonus_range'
  | 'kingdom_passive_training_reduction_range'
  | 'character_class_specialty_xp_gain_range'
  | 'crafting_skill_bonus_range'
  | 'gold_gain_range'
  | 'gold_dust_gain_range'
  | 'shards_gain_range'
  | 'copper_coin_gain_range'
  | 'item_drop_chance_increase_range'
  | 'unique_item_drop_chance_increase_range'
  | 'mythic_item_drop_chance_increase_range'
  | 'cosmic_item_drop_chance_increase_range'
  | 'enemy_strength_increase_range'
  | 'enemy_healing_increase_range'
  | 'enemy_spell_evasion_range'
  | 'enemy_affix_resistance_range'
  | 'enemy_entrancing_chance_range'
  | 'enemy_devouring_light_chance_range'
  | 'enemy_devouring_darkness_chance_range'
  | 'enemy_ambush_chance_range'
  | 'enemy_ambush_resistance_range'
  | 'enemy_counter_chance_range'
  | 'enemy_counter_resistance_range'
  | 'enemy_quest_item_drop_chance_increase_range'
  | 'monster_xp_increase_range'
  | 'monster_gold_drop_increase_range'
  | 'monster_atonement_range';

const RANGE_FIELDS: readonly LocationGemRangeField[] = [
  'character_xp_bonus_range',
  'character_class_rank_xp_bonus_range',
  'kingdom_passive_training_reduction_range',
  'character_class_specialty_xp_gain_range',
  'crafting_skill_bonus_range',
  'gold_gain_range',
  'gold_dust_gain_range',
  'shards_gain_range',
  'copper_coin_gain_range',
  'item_drop_chance_increase_range',
  'unique_item_drop_chance_increase_range',
  'mythic_item_drop_chance_increase_range',
  'cosmic_item_drop_chance_increase_range',
  'enemy_strength_increase_range',
  'enemy_healing_increase_range',
  'enemy_spell_evasion_range',
  'enemy_affix_resistance_range',
  'enemy_entrancing_chance_range',
  'enemy_devouring_light_chance_range',
  'enemy_devouring_darkness_chance_range',
  'enemy_ambush_chance_range',
  'enemy_ambush_resistance_range',
  'enemy_counter_chance_range',
  'enemy_counter_resistance_range',
  'enemy_quest_item_drop_chance_increase_range',
  'monster_xp_increase_range',
  'monster_gold_drop_increase_range',
  'monster_atonement_range',
];

const RANGE_PATTERN = /^\d+(\.\d+)?-\d+(\.\d+)?$/;

const requireLocationId = (value: number | null): number => {
  if (value === null) {
    throw new Error('A Location is required before building the request.');
  }

  return value;
};

export const createLocationGemFormState = (
  locationGem: LocationGemFormDefinition | null
): LocationGemFormStateDefinition => ({
  location_id: locationGem?.location_id ?? null,
  name: locationGem?.name ?? '',
  description: locationGem?.description ?? '',
  crafting_skill_ids: locationGem?.crafting_skill_ids ?? [],
  monster_atonement: locationGem?.monster_atonement ?? null,
  character_xp_bonus_range: locationGem?.character_xp_bonus_range ?? '',
  character_class_rank_xp_bonus_range:
    locationGem?.character_class_rank_xp_bonus_range ?? '',
  kingdom_passive_training_reduction_range:
    locationGem?.kingdom_passive_training_reduction_range ?? '',
  character_class_specialty_xp_gain_range:
    locationGem?.character_class_specialty_xp_gain_range ?? '',
  crafting_skill_bonus_range: locationGem?.crafting_skill_bonus_range ?? '',
  gold_gain_range: locationGem?.gold_gain_range ?? '',
  gold_dust_gain_range: locationGem?.gold_dust_gain_range ?? '',
  shards_gain_range: locationGem?.shards_gain_range ?? '',
  copper_coin_gain_range: locationGem?.copper_coin_gain_range ?? '',
  item_drop_chance_increase_range:
    locationGem?.item_drop_chance_increase_range ?? '',
  unique_item_drop_chance_increase_range:
    locationGem?.unique_item_drop_chance_increase_range ?? '',
  mythic_item_drop_chance_increase_range:
    locationGem?.mythic_item_drop_chance_increase_range ?? '',
  cosmic_item_drop_chance_increase_range:
    locationGem?.cosmic_item_drop_chance_increase_range ?? '',
  enemy_strength_increase_range:
    locationGem?.enemy_strength_increase_range ?? '',
  enemy_healing_increase_range: locationGem?.enemy_healing_increase_range ?? '',
  enemy_spell_evasion_range: locationGem?.enemy_spell_evasion_range ?? '',
  enemy_affix_resistance_range: locationGem?.enemy_affix_resistance_range ?? '',
  enemy_entrancing_chance_range:
    locationGem?.enemy_entrancing_chance_range ?? '',
  enemy_devouring_light_chance_range:
    locationGem?.enemy_devouring_light_chance_range ?? '',
  enemy_devouring_darkness_chance_range:
    locationGem?.enemy_devouring_darkness_chance_range ?? '',
  enemy_ambush_chance_range: locationGem?.enemy_ambush_chance_range ?? '',
  enemy_ambush_resistance_range:
    locationGem?.enemy_ambush_resistance_range ?? '',
  enemy_counter_chance_range: locationGem?.enemy_counter_chance_range ?? '',
  enemy_counter_resistance_range:
    locationGem?.enemy_counter_resistance_range ?? '',
  enemy_quest_item_drop_chance_increase_range:
    locationGem?.enemy_quest_item_drop_chance_increase_range ?? '',
  monster_xp_increase_range: locationGem?.monster_xp_increase_range ?? '',
  monster_gold_drop_increase_range:
    locationGem?.monster_gold_drop_increase_range ?? '',
  monster_atonement_range: locationGem?.monster_atonement_range ?? '',
});

export const buildLocationGemRequestPayload = (
  state: LocationGemFormStateDefinition
): LocationGemRequestDefinition => ({
  location_id: requireLocationId(state.location_id),
  name: state.name,
  description: state.description.trim() === '' ? null : state.description,
  crafting_skill_ids: state.crafting_skill_ids,
  monster_atonement: state.monster_atonement,
  character_xp_bonus_range:
    state.character_xp_bonus_range.trim() === ''
      ? null
      : state.character_xp_bonus_range.trim(),
  character_class_rank_xp_bonus_range:
    state.character_class_rank_xp_bonus_range.trim() === ''
      ? null
      : state.character_class_rank_xp_bonus_range.trim(),
  kingdom_passive_training_reduction_range:
    state.kingdom_passive_training_reduction_range.trim() === ''
      ? null
      : state.kingdom_passive_training_reduction_range.trim(),
  character_class_specialty_xp_gain_range:
    state.character_class_specialty_xp_gain_range.trim() === ''
      ? null
      : state.character_class_specialty_xp_gain_range.trim(),
  crafting_skill_bonus_range:
    state.crafting_skill_bonus_range.trim() === ''
      ? null
      : state.crafting_skill_bonus_range.trim(),
  gold_gain_range:
    state.gold_gain_range.trim() === '' ? null : state.gold_gain_range.trim(),
  gold_dust_gain_range:
    state.gold_dust_gain_range.trim() === ''
      ? null
      : state.gold_dust_gain_range.trim(),
  shards_gain_range:
    state.shards_gain_range.trim() === ''
      ? null
      : state.shards_gain_range.trim(),
  copper_coin_gain_range:
    state.copper_coin_gain_range.trim() === ''
      ? null
      : state.copper_coin_gain_range.trim(),
  item_drop_chance_increase_range:
    state.item_drop_chance_increase_range.trim() === ''
      ? null
      : state.item_drop_chance_increase_range.trim(),
  unique_item_drop_chance_increase_range:
    state.unique_item_drop_chance_increase_range.trim() === ''
      ? null
      : state.unique_item_drop_chance_increase_range.trim(),
  mythic_item_drop_chance_increase_range:
    state.mythic_item_drop_chance_increase_range.trim() === ''
      ? null
      : state.mythic_item_drop_chance_increase_range.trim(),
  cosmic_item_drop_chance_increase_range:
    state.cosmic_item_drop_chance_increase_range.trim() === ''
      ? null
      : state.cosmic_item_drop_chance_increase_range.trim(),
  enemy_strength_increase_range:
    state.enemy_strength_increase_range.trim() === ''
      ? null
      : state.enemy_strength_increase_range.trim(),
  enemy_healing_increase_range:
    state.enemy_healing_increase_range.trim() === ''
      ? null
      : state.enemy_healing_increase_range.trim(),
  enemy_spell_evasion_range:
    state.enemy_spell_evasion_range.trim() === ''
      ? null
      : state.enemy_spell_evasion_range.trim(),
  enemy_affix_resistance_range:
    state.enemy_affix_resistance_range.trim() === ''
      ? null
      : state.enemy_affix_resistance_range.trim(),
  enemy_entrancing_chance_range:
    state.enemy_entrancing_chance_range.trim() === ''
      ? null
      : state.enemy_entrancing_chance_range.trim(),
  enemy_devouring_light_chance_range:
    state.enemy_devouring_light_chance_range.trim() === ''
      ? null
      : state.enemy_devouring_light_chance_range.trim(),
  enemy_devouring_darkness_chance_range:
    state.enemy_devouring_darkness_chance_range.trim() === ''
      ? null
      : state.enemy_devouring_darkness_chance_range.trim(),
  enemy_ambush_chance_range:
    state.enemy_ambush_chance_range.trim() === ''
      ? null
      : state.enemy_ambush_chance_range.trim(),
  enemy_ambush_resistance_range:
    state.enemy_ambush_resistance_range.trim() === ''
      ? null
      : state.enemy_ambush_resistance_range.trim(),
  enemy_counter_chance_range:
    state.enemy_counter_chance_range.trim() === ''
      ? null
      : state.enemy_counter_chance_range.trim(),
  enemy_counter_resistance_range:
    state.enemy_counter_resistance_range.trim() === ''
      ? null
      : state.enemy_counter_resistance_range.trim(),
  enemy_quest_item_drop_chance_increase_range:
    state.enemy_quest_item_drop_chance_increase_range.trim() === ''
      ? null
      : state.enemy_quest_item_drop_chance_increase_range.trim(),
  monster_xp_increase_range:
    state.monster_xp_increase_range.trim() === ''
      ? null
      : state.monster_xp_increase_range.trim(),
  monster_gold_drop_increase_range:
    state.monster_gold_drop_increase_range.trim() === ''
      ? null
      : state.monster_gold_drop_increase_range.trim(),
  monster_atonement_range:
    state.monster_atonement_range.trim() === ''
      ? null
      : state.monster_atonement_range.trim(),
});

const computeLocationGemFieldErrors = (
  state: LocationGemFormStateDefinition
): LocationGemFormErrorsDefinition => {
  const errors: LocationGemFormErrorsDefinition = {};

  if (state.location_id === null) {
    errors.location_id = 'Select a Location.';
  }

  if (state.name.trim() === '') {
    errors.name = 'Enter a Location Gem profile name.';
  }

  RANGE_FIELDS.forEach((field) => {
    const value = state[field].trim();

    if (value !== '' && !RANGE_PATTERN.test(value)) {
      errors[field] =
        'The range must contain two nonnegative numeric values separated by a hyphen.';
    }
  });

  if (state.monster_atonement !== null && !isGemType(state.monster_atonement)) {
    errors.monster_atonement = 'Select a valid monster atonement.';
  }

  return errors;
};

export const validateLocationGemForm = (
  state: LocationGemFormStateDefinition
): LocationGemValidationResultDefinition => {
  const errors = computeLocationGemFieldErrors(state);

  return {
    is_valid: Object.keys(errors).length === 0,
    field_errors: errors,
    form_error: null,
  };
};

const LOCATION_GEM_STEP_FIELDS: ReadonlyArray<
  ReadonlyArray<keyof LocationGemFormStateDefinition>
> = [
  ['location_id', 'name', 'description'],
  RANGE_FIELDS.slice(0, 5),
  RANGE_FIELDS.slice(5, 14),
  RANGE_FIELDS.slice(14, 25),
  [...RANGE_FIELDS.slice(25), 'monster_atonement'],
];

export const validateLocationGemStep = (
  state: LocationGemFormStateDefinition,
  stepIndex: number
): LocationGemValidationResultDefinition => {
  const allErrors = computeLocationGemFieldErrors(state);
  const stepFields = LOCATION_GEM_STEP_FIELDS[stepIndex] ?? [];

  const errors = Object.fromEntries(
    Object.entries(allErrors).filter(([field]) =>
      stepFields.some((candidate) => candidate === field)
    )
  );

  return {
    is_valid: Object.keys(errors).length === 0,
    field_errors: errors,
    form_error: null,
  };
};
