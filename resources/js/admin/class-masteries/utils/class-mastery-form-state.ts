import ClassMasteryFormDefinition from '../api/definitions/class-mastery-form-definition';
import ClassMasteryRequestDefinition from '../api/definitions/class-mastery-request-definition';
import ClassMasteryFormErrorsDefinition from '../definitions/class-mastery-form-errors-definition';
import ClassMasteryFormStateDefinition from '../definitions/class-mastery-form-state-definition';
import ClassMasteryValidationResultDefinition from '../definitions/class-mastery-validation-result-definition';
import { isAttackType } from '../enums/attack-type';

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

const toValidationResult = (
  field_errors: ClassMasteryFormErrorsDefinition
): ClassMasteryValidationResultDefinition => ({
  is_valid: Object.keys(field_errors).length === 0,
  field_errors,
  form_error: null,
});

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

export const createClassMasteryFormState = (
  classMastery: ClassMasteryFormDefinition | null
): ClassMasteryFormStateDefinition => {
  if (!classMastery) {
    return {
      game_class_id: null,
      name: '',
      description: '',
      requires_class_rank_level: '0',
      specialty_damage: '',
      increase_specialty_damage_per_level: '',
      specialty_damage_uses_damage_stat_amount: '',
      attack_type_required: null,
      base_damage_mod: '',
      base_ac_mod: '',
      base_healing_mod: '',
      base_spell_damage_mod: '',
      health_mod: '',
      base_damage_stat_increase: '',
      spell_evasion: '',
      affix_damage_reduction: '',
      healing_reduction: '',
      skill_reduction: '',
      resistance_reduction: '',
    };
  }

  return {
    game_class_id: classMastery.game_class_id,
    name: classMastery.name,
    description: classMastery.description ?? '',
    requires_class_rank_level: toStringValue(
      classMastery.requires_class_rank_level
    ),
    specialty_damage: toStringValue(classMastery.specialty_damage),
    increase_specialty_damage_per_level: toStringValue(
      classMastery.increase_specialty_damage_per_level
    ),
    specialty_damage_uses_damage_stat_amount: toStringValue(
      classMastery.specialty_damage_uses_damage_stat_amount
    ),
    attack_type_required: classMastery.attack_type_required,
    base_damage_mod: toStringValue(classMastery.base_damage_mod),
    base_ac_mod: toStringValue(classMastery.base_ac_mod),
    base_healing_mod: toStringValue(classMastery.base_healing_mod),
    base_spell_damage_mod: toStringValue(classMastery.base_spell_damage_mod),
    health_mod: toStringValue(classMastery.health_mod),
    base_damage_stat_increase: toStringValue(
      classMastery.base_damage_stat_increase
    ),
    spell_evasion: toStringValue(classMastery.spell_evasion),
    affix_damage_reduction: toStringValue(classMastery.affix_damage_reduction),
    healing_reduction: toStringValue(classMastery.healing_reduction),
    skill_reduction: toStringValue(classMastery.skill_reduction),
    resistance_reduction: toStringValue(classMastery.resistance_reduction),
  };
};

export const buildClassMasteryRequestPayload = (
  state: ClassMasteryFormStateDefinition
): ClassMasteryRequestDefinition => ({
  game_class_id: state.game_class_id,
  name: state.name,
  description: state.description.trim(),
  requires_class_rank_level:
    toNumberOrNull(state.requires_class_rank_level) ?? 0,
  specialty_damage: toNumberOrNull(state.specialty_damage),
  increase_specialty_damage_per_level: toNumberOrNull(
    state.increase_specialty_damage_per_level
  ),
  specialty_damage_uses_damage_stat_amount: toNumberOrNull(
    state.specialty_damage_uses_damage_stat_amount
  ),
  attack_type_required: state.attack_type_required,
  base_damage_mod: toNumberOrNull(state.base_damage_mod),
  base_ac_mod: toNumberOrNull(state.base_ac_mod),
  base_healing_mod: toNumberOrNull(state.base_healing_mod),
  base_spell_damage_mod: toNumberOrNull(state.base_spell_damage_mod),
  health_mod: toNumberOrNull(state.health_mod),
  base_damage_stat_increase: toNumberOrNull(state.base_damage_stat_increase),
  spell_evasion: toNumberOrNull(state.spell_evasion),
  affix_damage_reduction: toNumberOrNull(state.affix_damage_reduction),
  healing_reduction: toNumberOrNull(state.healing_reduction),
  skill_reduction: toNumberOrNull(state.skill_reduction),
  resistance_reduction: toNumberOrNull(state.resistance_reduction),
});

/**
 * Validate the fields belonging to the Identity step before the wizard advances.
 */
export const validateClassMasteryIdentityStep = (
  state: ClassMasteryFormStateDefinition
): ClassMasteryValidationResultDefinition => {
  const errors: ClassMasteryFormErrorsDefinition = {};

  if (state.game_class_id === null) {
    errors.game_class_id = 'Select the owning Class.';
  }

  if (state.name.trim() === '') {
    errors.name = 'Enter a Class Mastery name.';
  }

  if (state.description.trim() === '') {
    errors.description = 'Enter a Class Mastery description.';
  }

  const rank = Number(state.requires_class_rank_level);

  if (
    state.requires_class_rank_level.trim() === '' ||
    !Number.isInteger(rank)
  ) {
    errors.requires_class_rank_level = 'Enter the required Class Rank level.';
  } else if (rank < 0 || rank > 100) {
    errors.requires_class_rank_level =
      'Enter a Class Rank level from 0 to 100.';
  }

  return toValidationResult(errors);
};

const validateOptionalNumber = (value: string): boolean =>
  value.trim() === '' || Number.isFinite(Number(value));
const validateOptionalInteger = (value: string): boolean =>
  value.trim() === '' || Number.isInteger(Number(value));

export const validateClassMasteryAttackStep = (
  state: ClassMasteryFormStateDefinition
): ClassMasteryValidationResultDefinition => {
  const errors: ClassMasteryFormErrorsDefinition = {};
  if (!validateOptionalInteger(state.specialty_damage))
    errors.specialty_damage = 'Enter a whole number.';
  if (!validateOptionalInteger(state.increase_specialty_damage_per_level))
    errors.increase_specialty_damage_per_level = 'Enter a whole number.';
  if (!validateOptionalNumber(state.specialty_damage_uses_damage_stat_amount))
    errors.specialty_damage_uses_damage_stat_amount = 'Enter a number.';
  if (
    state.attack_type_required !== null &&
    !isAttackType(state.attack_type_required)
  )
    errors.attack_type_required = 'Select a valid attack type.';
  return toValidationResult(errors);
};

const validateNumericFields = (
  state: ClassMasteryFormStateDefinition,
  fields: ReadonlyArray<keyof ClassMasteryFormStateDefinition>
): ClassMasteryFormErrorsDefinition => {
  const errors: ClassMasteryFormErrorsDefinition = {};
  fields.forEach((field) => {
    const value = state[field];
    if (typeof value === 'string' && !validateOptionalNumber(value))
      errors[field] = 'Enter a number.';
  });
  return errors;
};

export const validateClassMasteryModifiersStep = (
  state: ClassMasteryFormStateDefinition
): ClassMasteryValidationResultDefinition =>
  toValidationResult(
    validateNumericFields(state, [
      'base_damage_mod',
      'base_ac_mod',
      'base_healing_mod',
      'base_spell_damage_mod',
      'health_mod',
      'base_damage_stat_increase',
    ])
  );
export const validateClassMasteryEvasionStep = (
  state: ClassMasteryFormStateDefinition
): ClassMasteryValidationResultDefinition =>
  toValidationResult(
    validateNumericFields(state, [
      'spell_evasion',
      'affix_damage_reduction',
      'healing_reduction',
      'skill_reduction',
      'resistance_reduction',
    ])
  );

export const validateClassMasteryForm = (
  state: ClassMasteryFormStateDefinition
): ClassMasteryValidationResultDefinition =>
  toValidationResult({
    ...validateClassMasteryIdentityStep(state).field_errors,
    ...validateClassMasteryAttackStep(state).field_errors,
    ...validateClassMasteryModifiersStep(state).field_errors,
    ...validateClassMasteryEvasionStep(state).field_errors,
  });
