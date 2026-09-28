import { useEffect, useState } from 'react';

import UseGemAbilityFormDefinition from './definitions/use-gem-ability-form-definition';
import GemAbilityFormDefinition from '../api/definitions/gem-ability-form-definition';
import { useGemAbilityForEdit } from '../api/hooks/use-gem-ability-for-edit';
import { useGemAbilityFormOptions } from '../api/hooks/use-gem-ability-form-options';
import { useSaveGemAbility } from '../api/hooks/use-save-gem-ability';
import GemAbilityFormErrorsDefinition from '../definitions/gem-ability-form-errors-definition';
import GemAbilityFormStateDefinition from '../definitions/gem-ability-form-state-definition';
import {
  buildGemAbilityRequestPayload,
  createGemAbilityFormState,
  validateGemAbilityEffectStep,
  validateGemAbilityForm,
  validateGemAbilityIdentityStep,
} from '../utils/gem-ability-form-state';

const STEP_VALIDATORS = [
  validateGemAbilityIdentityStep,
  validateGemAbilityEffectStep,
];

export const useGemAbilityForm = (
  gemAbilityId: number | null
): UseGemAbilityFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useGemAbilityFormOptions();
  const {
    gem_ability: existingGemAbility,
    loading: loadingGemAbility,
    error: gemAbilityError,
  } = useGemAbilityForEdit(gemAbilityId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveGemAbility();

  const [formState, setFormState] = useState<GemAbilityFormStateDefinition>(
    () => createGemAbilityFormState(null)
  );
  const [errors, setErrors] = useState<GemAbilityFormErrorsDefinition>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (gemAbilityId !== null && !existingGemAbility) {
      return;
    }

    setFormState(createGemAbilityFormState(existingGemAbility));
    setInitialized(true);
  }, [initialized, gemAbilityId, existingGemAbility]);

  const updateField: UseGemAbilityFormDefinition['update_field'] = (
    field,
    value
  ) => {
    setFormState((previous) => ({ ...previous, [field]: value }));
    clearServerFieldError(field);
    setFormError(null);
    setErrors((previous) => {
      if (!(field in previous)) {
        return previous;
      }

      const next = { ...previous };

      delete next[field];

      return next;
    });
  };

  const submit = async (): Promise<GemAbilityFormDefinition | null> => {
    const result = validateGemAbilityForm(formState);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    if (!result.is_valid) {
      return null;
    }

    return save(gemAbilityId, buildGemAbilityRequestPayload(formState));
  };

  const validateStep = (stepIndex: number): boolean => {
    const result = STEP_VALIDATORS[stepIndex]?.(formState) ?? {
      is_valid: true,
      field_errors: {},
      form_error: null,
    };

    setErrors(result.field_errors);
    setFormError(result.form_error);

    return result.is_valid;
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingGemAbility || !initialized,
    load_error: optionsError ?? gemAbilityError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    form_error: formError,
    submit,
    validate_step: validateStep,
  };
};
