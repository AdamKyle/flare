import SkillFormErrorsDefinition from '../definitions/skill-form-errors-definition';
import SkillFormStateDefinition from '../definitions/skill-form-state-definition';
import SkillValidationResultDefinition from '../definitions/skill-validation-result-definition';
import { isSkillType } from '../enums/skill-type';

type SkillOptionalNumberField =
  | 'base_damage_mod_bonus_per_level'
  | 'base_healing_mod_bonus_per_level'
  | 'base_ac_mod_bonus_per_level'
  | 'skill_bonus_per_level'
  | 'class_bonus'
  | 'fight_time_out_mod_bonus_per_level'
  | 'move_time_out_mod_bonus_per_level'
  | 'unit_time_reduction'
  | 'building_time_reduction'
  | 'unit_movement_time_reduction';

const toValidationResult = (
  fieldErrors: SkillFormErrorsDefinition
): SkillValidationResultDefinition => ({
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

const isValidOptionalNumberString = (value: string): boolean =>
  value.trim() === '' || Number.isFinite(Number(value));

const validateOptionalNumbers = (
  state: SkillFormStateDefinition,
  fields: SkillOptionalNumberField[]
): SkillValidationResultDefinition => {
  const errors: SkillFormErrorsDefinition = {};

  fields.forEach((field) => {
    if (!isValidOptionalNumberString(state[field])) {
      errors[field] = 'Enter a number or leave this empty.';
    }
  });

  return toValidationResult(errors);
};

export const validateSkillBasicStep = (
  state: SkillFormStateDefinition
): SkillValidationResultDefinition => {
  const errors: SkillFormErrorsDefinition = {};

  if (state.name.trim() === '') {
    errors.name = 'Enter a Skill name.';
  }

  if (state.description.trim() === '') {
    errors.description = 'Enter a Skill description.';
  }

  if (!isValidIntegerString(state.max_level)) {
    errors.max_level = 'Enter the Skill max level as a whole number.';
  }

  if (!isSkillType(state.type)) {
    errors.type = 'Select a Skill type.';
  }

  return toValidationResult(errors);
};

export const validateSkillCharacterModifiersStep = (
  state: SkillFormStateDefinition
): SkillValidationResultDefinition =>
  validateOptionalNumbers(state, [
    'base_damage_mod_bonus_per_level',
    'base_healing_mod_bonus_per_level',
    'base_ac_mod_bonus_per_level',
    'skill_bonus_per_level',
  ]);

export const validateSkillTimersAndClassStep = (
  state: SkillFormStateDefinition
): SkillValidationResultDefinition =>
  validateOptionalNumbers(state, [
    'class_bonus',
    'fight_time_out_mod_bonus_per_level',
    'move_time_out_mod_bonus_per_level',
  ]);

export const validateSkillKingdomModifiersStep = (
  state: SkillFormStateDefinition
): SkillValidationResultDefinition =>
  validateOptionalNumbers(state, [
    'unit_time_reduction',
    'building_time_reduction',
    'unit_movement_time_reduction',
  ]);

export const validateSkillForm = (
  state: SkillFormStateDefinition
): SkillValidationResultDefinition =>
  toValidationResult({
    ...validateSkillBasicStep(state).field_errors,
    ...validateSkillCharacterModifiersStep(state).field_errors,
    ...validateSkillTimersAndClassStep(state).field_errors,
    ...validateSkillKingdomModifiersStep(state).field_errors,
  });
