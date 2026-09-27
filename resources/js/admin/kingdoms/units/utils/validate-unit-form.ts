import UnitFormErrorsDefinition from '../definitions/unit-form-errors-definition';
import UnitFormStateDefinition from '../definitions/unit-form-state-definition';
import UnitValidationResultDefinition from '../definitions/unit-validation-result-definition';

type UnitRequiredIntegerField = 'attack' | 'defence' | 'time_to_recruit';

type UnitOptionalIntegerField =
  | 'wood_cost'
  | 'stone_cost'
  | 'clay_cost'
  | 'iron_cost'
  | 'steel_cost'
  | 'required_population';

type UnitOptionalNumberField = 'heal_percentage' | 'reduces_morale_by';

const toValidationResult = (
  fieldErrors: UnitFormErrorsDefinition
): UnitValidationResultDefinition => ({
  is_valid: Object.keys(fieldErrors).length === 0,
  field_errors: fieldErrors,
});

const isValidIntegerString = (value: string): boolean => {
  if (value.trim() === '') {
    return false;
  }

  const parsed = Number(value);

  return Number.isFinite(parsed) && Number.isInteger(parsed);
};

export const validateUnitBasicAndCombatStep = (
  state: UnitFormStateDefinition
): UnitValidationResultDefinition => {
  const errors: UnitFormErrorsDefinition = {};
  const integerFields: UnitRequiredIntegerField[] = [
    'attack',
    'defence',
    'time_to_recruit',
  ];
  const numberFields: UnitOptionalNumberField[] = [
    'heal_percentage',
    'reduces_morale_by',
  ];

  if (state.name.trim() === '') {
    errors.name = 'Enter a Unit name.';
  }

  if (state.description.trim() === '') {
    errors.description = 'Enter a Unit description.';
  }

  integerFields.forEach((field) => {
    if (!isValidIntegerString(state[field])) {
      errors[field] = 'Enter a whole number.';
    }
  });

  numberFields.forEach((field) => {
    if (state[field].trim() !== '' && !Number.isFinite(Number(state[field]))) {
      errors[field] = 'Enter a number or leave this empty.';
    }
  });

  return toValidationResult(errors);
};

export const validateUnitResourceCostsStep = (
  state: UnitFormStateDefinition
): UnitValidationResultDefinition => {
  const errors: UnitFormErrorsDefinition = {};
  const integerFields: UnitOptionalIntegerField[] = [
    'wood_cost',
    'stone_cost',
    'clay_cost',
    'iron_cost',
    'steel_cost',
    'required_population',
  ];

  integerFields.forEach((field) => {
    if (state[field].trim() !== '' && !isValidIntegerString(state[field])) {
      errors[field] = 'Enter a whole number or leave this empty.';
    }
  });

  return toValidationResult(errors);
};

export const validateUnitForm = (
  state: UnitFormStateDefinition
): UnitValidationResultDefinition =>
  toValidationResult({
    ...validateUnitBasicAndCombatStep(state).field_errors,
    ...validateUnitResourceCostsStep(state).field_errors,
  });
