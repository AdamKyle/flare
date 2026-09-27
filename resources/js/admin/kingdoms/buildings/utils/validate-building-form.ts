import BuildingFormErrorsDefinition from '../definitions/building-form-errors-definition';
import BuildingFormStateDefinition from '../definitions/building-form-state-definition';
import BuildingValidationResultDefinition from '../definitions/building-validation-result-definition';

type BuildingIntegerField =
  | 'max_level'
  | 'required_population'
  | 'base_durability'
  | 'base_defence'
  | 'wood_cost'
  | 'clay_cost'
  | 'stone_cost'
  | 'iron_cost'
  | 'increase_population_amount';

type BuildingNumberField =
  | 'time_to_build'
  | 'time_increase_amount'
  | 'increase_morale_amount'
  | 'decrease_morale_amount'
  | 'increase_wood_amount'
  | 'increase_clay_amount'
  | 'increase_stone_amount'
  | 'increase_iron_amount'
  | 'increase_durability_amount'
  | 'increase_defence_amount';

type BuildingOptionalIntegerField =
  'level_required' | 'steel_cost' | 'units_per_level' | 'only_at_level';

const toValidationResult = (
  fieldErrors: BuildingFormErrorsDefinition
): BuildingValidationResultDefinition => ({
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

const isValidNumberString = (value: string): boolean =>
  value.trim() !== '' && Number.isFinite(Number(value));

const toNumberOrZero = (value: string): number =>
  value.trim() === '' ? 0 : Number(value);

const collectErrors = (
  state: BuildingFormStateDefinition,
  integerFields: BuildingIntegerField[],
  numberFields: BuildingNumberField[],
  optionalIntegerFields: BuildingOptionalIntegerField[]
): BuildingFormErrorsDefinition => {
  const errors: BuildingFormErrorsDefinition = {};

  integerFields.forEach((field) => {
    if (!isValidIntegerString(state[field])) {
      errors[field] = 'Enter a whole number.';
    }
  });

  numberFields.forEach((field) => {
    if (!isValidNumberString(state[field])) {
      errors[field] = 'Enter a number.';
    }
  });

  optionalIntegerFields.forEach((field) => {
    if (state[field].trim() !== '' && !isValidIntegerString(state[field])) {
      errors[field] = 'Enter a whole number or leave this empty.';
    }
  });

  return errors;
};

export const validateBuildingBasicStep = (
  state: BuildingFormStateDefinition
): BuildingValidationResultDefinition => {
  const errors = collectErrors(
    state,
    ['max_level', 'required_population', 'base_durability', 'base_defence'],
    [],
    ['level_required']
  );

  if (state.name.trim() === '') {
    errors.name = 'Enter a Building name.';
  }

  if (state.description.trim() === '') {
    errors.description = 'Enter a Building description.';
  }

  return toValidationResult(errors);
};

export const validateBuildingUpgradeCostsStep = (
  state: BuildingFormStateDefinition
): BuildingValidationResultDefinition =>
  toValidationResult(
    collectErrors(
      state,
      ['wood_cost', 'clay_cost', 'stone_cost', 'iron_cost'],
      ['time_to_build', 'time_increase_amount'],
      ['steel_cost']
    )
  );

export const validateBuildingUpgradeEffectsStep = (
  state: BuildingFormStateDefinition
): BuildingValidationResultDefinition =>
  toValidationResult(
    collectErrors(
      state,
      ['increase_population_amount'],
      [
        'increase_morale_amount',
        'decrease_morale_amount',
        'increase_wood_amount',
        'increase_clay_amount',
        'increase_stone_amount',
        'increase_iron_amount',
        'increase_durability_amount',
        'increase_defence_amount',
      ],
      []
    )
  );

export const validateBuildingUnitRecruitmentStep = (
  state: BuildingFormStateDefinition
): BuildingValidationResultDefinition => {
  if (!state.trains_units) {
    return toValidationResult({});
  }

  const errors = collectErrors(
    state,
    [],
    [],
    ['units_per_level', 'only_at_level']
  );

  if (Object.keys(errors).length > 0) {
    return toValidationResult(errors);
  }

  const unitsPerLevel = toNumberOrZero(state.units_per_level);

  if (state.unit_ids.length === 0) {
    errors.unit_ids =
      'Select at least one Unit for a Building that trains Units.';
  }

  if (unitsPerLevel !== 0 && toNumberOrZero(state.only_at_level) !== 0) {
    errors.units_per_level =
      'Units cannot be recruited both per level and at a single level. Choose one.';
  }

  if (state.unit_ids.length * unitsPerLevel > toNumberOrZero(state.max_level)) {
    errors.units_per_level =
      'The number of Units multiplied by Units Per Level cannot exceed the Building max level.';
  }

  return toValidationResult(errors);
};

export const validateBuildingForm = (
  state: BuildingFormStateDefinition
): BuildingValidationResultDefinition =>
  toValidationResult({
    ...validateBuildingBasicStep(state).field_errors,
    ...validateBuildingUpgradeCostsStep(state).field_errors,
    ...validateBuildingUpgradeEffectsStep(state).field_errors,
    ...validateBuildingUnitRecruitmentStep(state).field_errors,
  });
