import { GemAbilityEffectType } from '../../../game/reusable-components/gem-ability/enums/gem-ability-effect-type';
import {
  GemAbilityType,
  isGemAbilityType,
} from '../../../game/reusable-components/gem-ability/enums/gem-ability-type';
import GemAbilityFormDefinition from '../api/definitions/gem-ability-form-definition';
import GemAbilityRequestDefinition from '../api/definitions/gem-ability-request-definition';
import GemAbilityFormErrorsDefinition from '../definitions/gem-ability-form-errors-definition';
import GemAbilityFormStateDefinition from '../definitions/gem-ability-form-state-definition';
import GemAbilityValidationResultDefinition from '../definitions/gem-ability-validation-result-definition';

const toStringValue = (value: number | null): string =>
  value === null ? '' : String(value);

const toNumberOrNull = (value: string): number | null =>
  value.trim() === '' ? null : Number(value);

const toValidationResult = (
  fieldErrors: GemAbilityFormErrorsDefinition
): GemAbilityValidationResultDefinition => ({
  is_valid: Object.keys(fieldErrors).length === 0,
  field_errors: fieldErrors,
  form_error: null,
});

const isFractionInRange = (value: string): boolean => {
  if (value.trim() === '') {
    return false;
  }

  const parsedValue = Number(value);

  return (
    Number.isFinite(parsedValue) && parsedValue >= 0.01 && parsedValue <= 1
  );
};

export const isActiveAbilityType = (abilityType: string | null): boolean =>
  abilityType === GemAbilityType.ACTIVE;

export const createGemAbilityFormState = (
  gemAbility: GemAbilityFormDefinition | null
): GemAbilityFormStateDefinition => {
  if (!gemAbility) {
    return {
      name: '',
      description: '',
      ability_type: null,
      effect_type: null,
      attack_types: [],
      proc_chance: '',
      effect_value: '',
      scaling_source: null,
      enabled: true,
    };
  }

  return {
    name: gemAbility.name,
    description: gemAbility.description,
    ability_type: gemAbility.ability_type,
    effect_type: gemAbility.effect_type,
    attack_types: gemAbility.attack_types,
    proc_chance: toStringValue(gemAbility.proc_chance),
    effect_value: toStringValue(gemAbility.effect_value),
    scaling_source: gemAbility.scaling_source,
    enabled: gemAbility.enabled,
  };
};

export const buildGemAbilityRequestPayload = (
  state: GemAbilityFormStateDefinition
): GemAbilityRequestDefinition => {
  const isActive = isActiveAbilityType(state.ability_type);

  return {
    name: state.name.trim(),
    description: state.description.trim(),
    ability_type: state.ability_type ?? '',
    effect_type: state.effect_type ?? '',
    attack_types: state.attack_types,
    proc_chance: isActive ? toNumberOrNull(state.proc_chance) : null,
    effect_value: toNumberOrNull(state.effect_value),
    scaling_source: isActive ? state.scaling_source : null,
    enabled: state.enabled,
  };
};

export const validateGemAbilityIdentityStep = (
  state: GemAbilityFormStateDefinition
): GemAbilityValidationResultDefinition => {
  const errors: GemAbilityFormErrorsDefinition = {};

  if (state.name.trim() === '') {
    errors.name = 'Enter a Gem Ability name.';
  }

  if (state.description.trim() === '') {
    errors.description = 'Enter a Gem Ability description.';
  }

  if (state.ability_type === null || !isGemAbilityType(state.ability_type)) {
    errors.ability_type =
      'Select whether the Gem Ability is active or passive.';
  }

  return toValidationResult(errors);
};

const validateEffectType = (
  state: GemAbilityFormStateDefinition
): string | null => {
  if (state.effect_type === null) {
    return 'Select the Gem Ability effect.';
  }

  const isBonusDamage = state.effect_type === GemAbilityEffectType.BONUS_DAMAGE;

  if (isActiveAbilityType(state.ability_type) !== isBonusDamage) {
    return 'Active abilities must deal bonus damage; passive abilities must use a modifier effect.';
  }

  return null;
};

const validateActiveOnlyFields = (
  state: GemAbilityFormStateDefinition
): GemAbilityFormErrorsDefinition => {
  if (!isActiveAbilityType(state.ability_type)) {
    return {};
  }

  const errors: GemAbilityFormErrorsDefinition = {};

  if (!isFractionInRange(state.proc_chance)) {
    errors.proc_chance = 'Enter a proc chance from 0.01 to 1.';
  }

  if (state.scaling_source === null) {
    errors.scaling_source = 'Select the scaling source for an active ability.';
  }

  return errors;
};

export const validateGemAbilityEffectStep = (
  state: GemAbilityFormStateDefinition
): GemAbilityValidationResultDefinition => {
  const errors: GemAbilityFormErrorsDefinition = {
    ...validateActiveOnlyFields(state),
  };
  const effectTypeError = validateEffectType(state);

  if (effectTypeError !== null) {
    errors.effect_type = effectTypeError;
  }

  if (state.attack_types.length === 0) {
    errors.attack_types = 'Select at least one attack action.';
  }

  if (!isFractionInRange(state.effect_value)) {
    errors.effect_value = 'Enter an effect value from 0.01 to 1.';
  }

  return toValidationResult(errors);
};

export const validateGemAbilityForm = (
  state: GemAbilityFormStateDefinition
): GemAbilityValidationResultDefinition =>
  toValidationResult({
    ...validateGemAbilityIdentityStep(state).field_errors,
    ...validateGemAbilityEffectStep(state).field_errors,
  });
