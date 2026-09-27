import PassiveSkillFormErrorsDefinition from '../definitions/passive-skill-form-errors-definition';
import PassiveSkillFormStateDefinition from '../definitions/passive-skill-form-state-definition';
import PassiveSkillValidationResultDefinition from '../definitions/passive-skill-validation-result-definition';
import { isPassiveSkillEffect } from '../enums/passive-skill-effect';

type PassiveSkillOptionalNumberField =
  | 'bonus_per_level'
  | 'capital_city_building_request_travel_time_reduction'
  | 'capital_city_unit_request_travel_time_reduction'
  | 'resource_request_time_reduction';

const toValidationResult = (
  fieldErrors: PassiveSkillFormErrorsDefinition
): PassiveSkillValidationResultDefinition => ({
  is_valid: Object.keys(fieldErrors).length === 0,
  field_errors: fieldErrors,
  form_error: null,
});

const isValidIntegerString = (value: string): boolean => {
  if (value.trim() === '') {
    return false;
  }

  const parsed = Number(value);

  return Number.isFinite(parsed) && Number.isInteger(parsed);
};

const isValidOptionalIntegerString = (value: string): boolean =>
  value.trim() === '' || isValidIntegerString(value);

const isValidOptionalNumberString = (value: string): boolean =>
  value.trim() === '' || Number.isFinite(Number(value));

export const validatePassiveSkillBasicStep = (
  state: PassiveSkillFormStateDefinition
): PassiveSkillValidationResultDefinition => {
  const errors: PassiveSkillFormErrorsDefinition = {};

  if (state.name.trim() === '') {
    errors.name = 'Enter a Passive Skill name.';
  }

  if (state.description.trim() === '') {
    errors.description = 'Enter a Passive Skill description.';
  }

  if (!isPassiveSkillEffect(state.effect_type)) {
    errors.effect_type = 'Select a Passive Skill effect.';
  }

  if (!isValidIntegerString(state.max_level)) {
    errors.max_level = 'Enter the max level as a whole number.';
  }

  if (!isValidIntegerString(state.hours_per_level)) {
    errors.hours_per_level = 'Enter the hours per level as a whole number.';
  }

  return toValidationResult(errors);
};

export const validatePassiveSkillBonusesStep = (
  state: PassiveSkillFormStateDefinition
): PassiveSkillValidationResultDefinition => {
  const errors: PassiveSkillFormErrorsDefinition = {};
  const numberFields: PassiveSkillOptionalNumberField[] = [
    'bonus_per_level',
    'capital_city_building_request_travel_time_reduction',
    'capital_city_unit_request_travel_time_reduction',
    'resource_request_time_reduction',
  ];

  numberFields.forEach((field) => {
    if (!isValidOptionalNumberString(state[field])) {
      errors[field] = 'Enter a number or leave this empty.';
    }
  });

  if (!isValidOptionalIntegerString(state.resource_bonus_per_level)) {
    errors.resource_bonus_per_level =
      'Enter a whole number or leave this empty.';
  }

  return toValidationResult(errors);
};

export const validatePassiveSkillTreeStep = (
  state: PassiveSkillFormStateDefinition,
  passiveSkillId: number | null
): PassiveSkillValidationResultDefinition => {
  const errors: PassiveSkillFormErrorsDefinition = {};

  if (passiveSkillId !== null && state.parent_skill_id === passiveSkillId) {
    errors.parent_skill_id = 'A Passive Skill cannot belong to itself.';
  }

  if (!isValidOptionalIntegerString(state.unlocks_at_level)) {
    errors.unlocks_at_level = 'Enter a whole number or leave this empty.';
  }

  return toValidationResult(errors);
};

export const validatePassiveSkillForm = (
  state: PassiveSkillFormStateDefinition,
  passiveSkillId: number | null
): PassiveSkillValidationResultDefinition =>
  toValidationResult({
    ...validatePassiveSkillBasicStep(state).field_errors,
    ...validatePassiveSkillBonusesStep(state).field_errors,
    ...validatePassiveSkillTreeStep(state, passiveSkillId).field_errors,
  });
