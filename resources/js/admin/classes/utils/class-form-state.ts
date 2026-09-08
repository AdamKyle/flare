import ClassFormDefinition from '../api/definitions/class-form-definition';
import ClassRequestDefinition from '../api/definitions/class-request-definition';
import ClassFormErrorsDefinition from '../definitions/class-form-errors-definition';
import ClassFormStateDefinition from '../definitions/class-form-state-definition';
import ClassValidationResultDefinition from '../definitions/class-validation-result-definition';
import { isCoreStat } from '../enums/core-stat';

const toValidationResult = (
  field_errors: ClassFormErrorsDefinition,
  form_error: string | null = null
): ClassValidationResultDefinition => ({
  is_valid: form_error === null && Object.keys(field_errors).length === 0,
  field_errors,
  form_error,
});

const MIN_REQUIRED_CLASS_LEVEL = 1;
const MAX_REQUIRED_CLASS_LEVEL = 100;

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

const toNumberOrZero = (value: string): number =>
  value.trim() === '' ? 0 : Number(value);

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

export const createClassFormState = (
  gameClass: ClassFormDefinition | null
): ClassFormStateDefinition => {
  if (!gameClass) {
    return {
      name: '',
      description: '',
      damage_stat: '',
      to_hit_stat: '',
      str_mod: '0',
      dur_mod: '0',
      dex_mod: '0',
      chr_mod: '0',
      int_mod: '0',
      agi_mod: '0',
      focus_mod: '0',
      accuracy_mod: '0',
      dodge_mod: '0',
      defense_mod: '0',
      looting_mod: '0',
      primary_required_class_id: null,
      secondary_required_class_id: null,
      primary_required_class_level: '',
      secondary_required_class_level: '',
    };
  }

  return {
    name: gameClass.name,
    description: gameClass.description ?? '',
    damage_stat: gameClass.damage_stat,
    to_hit_stat: gameClass.to_hit_stat,
    str_mod: toStringValue(gameClass.str_mod),
    dur_mod: toStringValue(gameClass.dur_mod),
    dex_mod: toStringValue(gameClass.dex_mod),
    chr_mod: toStringValue(gameClass.chr_mod),
    int_mod: toStringValue(gameClass.int_mod),
    agi_mod: toStringValue(gameClass.agi_mod),
    focus_mod: toStringValue(gameClass.focus_mod),
    accuracy_mod: toStringValue(gameClass.accuracy_mod),
    dodge_mod: toStringValue(gameClass.dodge_mod),
    defense_mod: toStringValue(gameClass.defense_mod),
    looting_mod: toStringValue(gameClass.looting_mod),
    primary_required_class_id: gameClass.primary_required_class_id,
    secondary_required_class_id: gameClass.secondary_required_class_id,
    primary_required_class_level: toStringValue(
      gameClass.primary_required_class_level
    ),
    secondary_required_class_level: toStringValue(
      gameClass.secondary_required_class_level
    ),
  };
};

export const buildClassRequestPayload = (
  state: ClassFormStateDefinition
): ClassRequestDefinition => ({
  name: state.name,
  description: state.description.trim() === '' ? null : state.description,
  damage_stat: state.damage_stat,
  to_hit_stat: state.to_hit_stat,
  str_mod: toNumberOrZero(state.str_mod),
  dur_mod: toNumberOrZero(state.dur_mod),
  dex_mod: toNumberOrZero(state.dex_mod),
  chr_mod: toNumberOrZero(state.chr_mod),
  int_mod: toNumberOrZero(state.int_mod),
  agi_mod: toNumberOrZero(state.agi_mod),
  focus_mod: toNumberOrZero(state.focus_mod),
  accuracy_mod: toNumberOrZero(state.accuracy_mod),
  dodge_mod: toNumberOrZero(state.dodge_mod),
  defense_mod: toNumberOrZero(state.defense_mod),
  looting_mod: toNumberOrZero(state.looting_mod),
  primary_required_class_id: state.primary_required_class_id,
  secondary_required_class_id: state.secondary_required_class_id,
  primary_required_class_level: toNumberOrNull(
    state.primary_required_class_level
  ),
  secondary_required_class_level: toNumberOrNull(
    state.secondary_required_class_level
  ),
});

const isValidIntegerString = (value: string): boolean => {
  if (value.trim() === '') {
    return false;
  }

  const parsed = Number(value);

  return Number.isFinite(parsed) && Number.isInteger(parsed);
};

const isValidNumberString = (value: string): boolean => {
  if (value.trim() === '') {
    return false;
  }

  return Number.isFinite(Number(value));
};

const isValidRequiredClassLevel = (value: string): boolean => {
  if (!isValidIntegerString(value)) {
    return false;
  }

  const parsed = Number(value);

  return (
    parsed >= MIN_REQUIRED_CLASS_LEVEL && parsed <= MAX_REQUIRED_CLASS_LEVEL
  );
};

export const validateClassBasicStep = (
  state: ClassFormStateDefinition
): ClassValidationResultDefinition => {
  const errors: ClassFormErrorsDefinition = {};

  if (state.name.trim() === '') {
    errors.name = 'Enter a Class name.';
  }

  if (!isCoreStat(state.damage_stat)) {
    errors.damage_stat = 'Select a valid damage stat.';
  }

  if (!isCoreStat(state.to_hit_stat)) {
    errors.to_hit_stat = 'Select a valid to-hit stat.';
  }

  return toValidationResult(errors);
};

type ClassIntegerStringField =
  | 'str_mod'
  | 'dur_mod'
  | 'dex_mod'
  | 'chr_mod'
  | 'int_mod'
  | 'agi_mod'
  | 'focus_mod';

type ClassNumberStringField =
  'accuracy_mod' | 'dodge_mod' | 'defense_mod' | 'looting_mod';

export const validateClassAttributesStep = (
  state: ClassFormStateDefinition
): ClassValidationResultDefinition => {
  const errors: ClassFormErrorsDefinition = {};
  const integerFields: ClassIntegerStringField[] = [
    'str_mod',
    'dur_mod',
    'dex_mod',
    'chr_mod',
    'int_mod',
    'agi_mod',
    'focus_mod',
  ];

  integerFields.forEach((field) => {
    if (!isValidIntegerString(state[field])) {
      errors[field] = 'Enter a whole number.';
    }
  });

  return toValidationResult(errors);
};

export const validateClassCombatStep = (
  state: ClassFormStateDefinition
): ClassValidationResultDefinition => {
  const errors: ClassFormErrorsDefinition = {};
  const numberFields: ClassNumberStringField[] = [
    'accuracy_mod',
    'dodge_mod',
    'defense_mod',
    'looting_mod',
  ];

  numberFields.forEach((field) => {
    if (!isValidNumberString(state[field])) {
      errors[field] = 'Enter a number.';
    }
  });

  return toValidationResult(errors);
};

export const validateClassUnlockStep = (
  state: ClassFormStateDefinition,
  classId: number | null
): ClassValidationResultDefinition => {
  const errors: ClassFormErrorsDefinition = {};
  const populatedCount = [
    state.primary_required_class_id,
    state.secondary_required_class_id,
    state.primary_required_class_level,
    state.secondary_required_class_level,
  ].filter((value) => value !== null && String(value).trim() !== '').length;

  if (populatedCount === 0) {
    return toValidationResult(errors);
  }

  if (populatedCount < 4) {
    if (state.primary_required_class_id === null) {
      errors.primary_required_class_id =
        'Select the primary prerequisite Class.';
    }

    if (state.primary_required_class_level.trim() === '') {
      errors.primary_required_class_level =
        'Enter the required primary Class rank.';
    }

    if (state.secondary_required_class_id === null) {
      errors.secondary_required_class_id =
        'Select the secondary prerequisite Class.';
    }

    if (state.secondary_required_class_level.trim() === '') {
      errors.secondary_required_class_level =
        'Enter the required secondary Class rank.';
    }

    return toValidationResult(
      errors,
      'A special Class requires both prerequisite Classes and both required levels, or none of them.'
    );
  }

  if (state.primary_required_class_id === state.secondary_required_class_id) {
    errors.secondary_required_class_id =
      'Select a different secondary prerequisite Class.';

    return toValidationResult(
      errors,
      'The primary and secondary prerequisite Classes must be different.'
    );
  }

  if (
    state.primary_required_class_id === classId ||
    state.secondary_required_class_id === classId
  ) {
    if (state.primary_required_class_id === classId) {
      errors.primary_required_class_id = 'A Class cannot require itself.';
    }

    if (state.secondary_required_class_id === classId) {
      errors.secondary_required_class_id = 'A Class cannot require itself.';
    }

    return toValidationResult(errors, 'A Class cannot require itself.');
  }

  if (!isValidRequiredClassLevel(state.primary_required_class_level)) {
    errors.primary_required_class_level =
      'Enter a whole number between 1 and 100.';
  }

  if (!isValidRequiredClassLevel(state.secondary_required_class_level)) {
    errors.secondary_required_class_level =
      'Enter a whole number between 1 and 100.';
  }

  return toValidationResult(errors);
};

export const validateClassForm = (
  state: ClassFormStateDefinition,
  classId: number | null
): ClassValidationResultDefinition => {
  const basic = validateClassBasicStep(state);
  const attributes = validateClassAttributesStep(state);
  const combat = validateClassCombatStep(state);
  const unlock = validateClassUnlockStep(state, classId);

  return toValidationResult(
    {
      ...basic.field_errors,
      ...attributes.field_errors,
      ...combat.field_errors,
      ...unlock.field_errors,
    },
    basic.form_error ??
      attributes.form_error ??
      combat.form_error ??
      unlock.form_error
  );
};
