import { isGemType } from '../../shared/enums/gem-type';
import {
  normalizeGemRangeValue,
  validateGemRangeValue,
} from '../../shared/gems/utils/gem-range-validation';
import MapGemFormDefinition from '../api/definitions/map-gem-form-definition';
import MapGemRequestDefinition from '../api/definitions/map-gem-request-definition';
import MapGemFormErrorsDefinition from '../definitions/map-gem-form-errors-definition';
import MapGemFormStateDefinition from '../definitions/map-gem-form-state-definition';
import MapGemValidationResultDefinition from '../definitions/map-gem-validation-result-definition';

type MapGemRangeField =
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
  | 'character_power_reduction_range'
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

const RANGE_FIELDS: readonly MapGemRangeField[] = [
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
  'character_power_reduction_range',
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

const requireGameMapId = (value: number | null): number => {
  if (value === null) {
    throw new Error('A Game Map is required before building the request.');
  }

  return value;
};

export const createMapGemFormState = (
  mapGem: MapGemFormDefinition | null
): MapGemFormStateDefinition => ({
  game_map_id: mapGem?.game_map_id ?? null,
  name: mapGem?.name ?? '',
  description: mapGem?.description ?? '',
  crafting_skill_ids: mapGem?.crafting_skill_ids ?? [],
  monster_atonement: mapGem?.monster_atonement ?? null,
  character_xp_bonus_range: mapGem?.character_xp_bonus_range ?? '',
  character_class_rank_xp_bonus_range:
    mapGem?.character_class_rank_xp_bonus_range ?? '',
  kingdom_passive_training_reduction_range:
    mapGem?.kingdom_passive_training_reduction_range ?? '',
  character_class_specialty_xp_gain_range:
    mapGem?.character_class_specialty_xp_gain_range ?? '',
  crafting_skill_bonus_range: mapGem?.crafting_skill_bonus_range ?? '',
  gold_gain_range: mapGem?.gold_gain_range ?? '',
  gold_dust_gain_range: mapGem?.gold_dust_gain_range ?? '',
  shards_gain_range: mapGem?.shards_gain_range ?? '',
  copper_coin_gain_range: mapGem?.copper_coin_gain_range ?? '',
  item_drop_chance_increase_range:
    mapGem?.item_drop_chance_increase_range ?? '',
  unique_item_drop_chance_increase_range:
    mapGem?.unique_item_drop_chance_increase_range ?? '',
  mythic_item_drop_chance_increase_range:
    mapGem?.mythic_item_drop_chance_increase_range ?? '',
  cosmic_item_drop_chance_increase_range:
    mapGem?.cosmic_item_drop_chance_increase_range ?? '',
  character_power_reduction_range:
    mapGem?.character_power_reduction_range ?? '',
  enemy_strength_increase_range: mapGem?.enemy_strength_increase_range ?? '',
  enemy_healing_increase_range: mapGem?.enemy_healing_increase_range ?? '',
  enemy_spell_evasion_range: mapGem?.enemy_spell_evasion_range ?? '',
  enemy_affix_resistance_range: mapGem?.enemy_affix_resistance_range ?? '',
  enemy_entrancing_chance_range: mapGem?.enemy_entrancing_chance_range ?? '',
  enemy_devouring_light_chance_range:
    mapGem?.enemy_devouring_light_chance_range ?? '',
  enemy_devouring_darkness_chance_range:
    mapGem?.enemy_devouring_darkness_chance_range ?? '',
  enemy_ambush_chance_range: mapGem?.enemy_ambush_chance_range ?? '',
  enemy_ambush_resistance_range: mapGem?.enemy_ambush_resistance_range ?? '',
  enemy_counter_chance_range: mapGem?.enemy_counter_chance_range ?? '',
  enemy_counter_resistance_range: mapGem?.enemy_counter_resistance_range ?? '',
  enemy_quest_item_drop_chance_increase_range:
    mapGem?.enemy_quest_item_drop_chance_increase_range ?? '',
  monster_xp_increase_range: mapGem?.monster_xp_increase_range ?? '',
  monster_gold_drop_increase_range:
    mapGem?.monster_gold_drop_increase_range ?? '',
  monster_atonement_range: mapGem?.monster_atonement_range ?? '',
});

export const buildMapGemRequestPayload = (
  state: MapGemFormStateDefinition
): MapGemRequestDefinition => ({
  game_map_id: requireGameMapId(state.game_map_id),
  name: state.name,
  description: state.description.trim() === '' ? null : state.description,
  crafting_skill_ids: state.crafting_skill_ids,
  monster_atonement: state.monster_atonement,
  character_xp_bonus_range: normalizeGemRangeValue(
    state.character_xp_bonus_range
  ),
  character_class_rank_xp_bonus_range: normalizeGemRangeValue(
    state.character_class_rank_xp_bonus_range
  ),
  kingdom_passive_training_reduction_range: normalizeGemRangeValue(
    state.kingdom_passive_training_reduction_range
  ),
  character_class_specialty_xp_gain_range: normalizeGemRangeValue(
    state.character_class_specialty_xp_gain_range
  ),
  crafting_skill_bonus_range: normalizeGemRangeValue(
    state.crafting_skill_bonus_range
  ),
  gold_gain_range: normalizeGemRangeValue(state.gold_gain_range),
  gold_dust_gain_range: normalizeGemRangeValue(state.gold_dust_gain_range),
  shards_gain_range: normalizeGemRangeValue(state.shards_gain_range),
  copper_coin_gain_range: normalizeGemRangeValue(state.copper_coin_gain_range),
  item_drop_chance_increase_range: normalizeGemRangeValue(
    state.item_drop_chance_increase_range
  ),
  unique_item_drop_chance_increase_range: normalizeGemRangeValue(
    state.unique_item_drop_chance_increase_range
  ),
  mythic_item_drop_chance_increase_range: normalizeGemRangeValue(
    state.mythic_item_drop_chance_increase_range
  ),
  cosmic_item_drop_chance_increase_range: normalizeGemRangeValue(
    state.cosmic_item_drop_chance_increase_range
  ),
  character_power_reduction_range: normalizeGemRangeValue(
    state.character_power_reduction_range
  ),
  enemy_strength_increase_range: normalizeGemRangeValue(
    state.enemy_strength_increase_range
  ),
  enemy_healing_increase_range: normalizeGemRangeValue(
    state.enemy_healing_increase_range
  ),
  enemy_spell_evasion_range: normalizeGemRangeValue(
    state.enemy_spell_evasion_range
  ),
  enemy_affix_resistance_range: normalizeGemRangeValue(
    state.enemy_affix_resistance_range
  ),
  enemy_entrancing_chance_range: normalizeGemRangeValue(
    state.enemy_entrancing_chance_range
  ),
  enemy_devouring_light_chance_range: normalizeGemRangeValue(
    state.enemy_devouring_light_chance_range
  ),
  enemy_devouring_darkness_chance_range: normalizeGemRangeValue(
    state.enemy_devouring_darkness_chance_range
  ),
  enemy_ambush_chance_range: normalizeGemRangeValue(
    state.enemy_ambush_chance_range
  ),
  enemy_ambush_resistance_range: normalizeGemRangeValue(
    state.enemy_ambush_resistance_range
  ),
  enemy_counter_chance_range: normalizeGemRangeValue(
    state.enemy_counter_chance_range
  ),
  enemy_counter_resistance_range: normalizeGemRangeValue(
    state.enemy_counter_resistance_range
  ),
  enemy_quest_item_drop_chance_increase_range: normalizeGemRangeValue(
    state.enemy_quest_item_drop_chance_increase_range
  ),
  monster_xp_increase_range: normalizeGemRangeValue(
    state.monster_xp_increase_range
  ),
  monster_gold_drop_increase_range: normalizeGemRangeValue(
    state.monster_gold_drop_increase_range
  ),
  monster_atonement_range: normalizeGemRangeValue(
    state.monster_atonement_range
  ),
});

const computeMapGemFieldErrors = (
  state: MapGemFormStateDefinition
): MapGemFormErrorsDefinition => {
  const errors: MapGemFormErrorsDefinition = {};

  if (state.game_map_id === null) {
    errors.game_map_id = 'Select a Game Map.';
  }

  if (state.name.trim() === '') {
    errors.name = 'Enter a Map Gem profile name.';
  }

  RANGE_FIELDS.forEach((field) => {
    const rangeError = validateGemRangeValue(state[field]);

    if (rangeError !== null) {
      errors[field] = rangeError;
    }
  });

  if (state.monster_atonement !== null && !isGemType(state.monster_atonement)) {
    errors.monster_atonement = 'Select a valid monster atonement.';
  }

  return errors;
};

export const validateMapGemForm = (
  state: MapGemFormStateDefinition
): MapGemValidationResultDefinition => {
  const errors = computeMapGemFieldErrors(state);

  return {
    is_valid: Object.keys(errors).length === 0,
    field_errors: errors,
    form_error: null,
  };
};

const MAP_GEM_STEP_FIELDS: ReadonlyArray<
  ReadonlyArray<keyof MapGemFormStateDefinition>
> = [
  ['game_map_id', 'name', 'description'],
  RANGE_FIELDS.slice(0, 5),
  RANGE_FIELDS.slice(5, 14),
  RANGE_FIELDS.slice(14, 26),
  [...RANGE_FIELDS.slice(26), 'monster_atonement'],
];

export const validateMapGemStep = (
  state: MapGemFormStateDefinition,
  stepIndex: number
): MapGemValidationResultDefinition => {
  const allErrors = computeMapGemFieldErrors(state);
  const stepFields = MAP_GEM_STEP_FIELDS[stepIndex] ?? [];

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
