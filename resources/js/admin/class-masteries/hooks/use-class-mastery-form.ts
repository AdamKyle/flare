import { useEffect, useState } from 'react';

import UseClassMasteryFormDefinition from './definitions/use-class-mastery-form-definition';
import ClassMasteryFormDefinition from '../api/definitions/class-mastery-form-definition';
import { useClassMasteryForEdit } from '../api/hooks/use-class-mastery-for-edit';
import { useClassMasteryFormOptions } from '../api/hooks/use-class-mastery-form-options';
import { useSaveClassMastery } from '../api/hooks/use-save-class-mastery';
import ClassMasteryFormErrorsDefinition from '../definitions/class-mastery-form-errors-definition';
import ClassMasteryFormStateDefinition from '../definitions/class-mastery-form-state-definition';
import {
  buildClassMasteryRequestPayload,
  createClassMasteryFormState,
  validateClassMasteryForm,
  validateClassMasteryAttackStep,
  validateClassMasteryEvasionStep,
  validateClassMasteryIdentityStep,
  validateClassMasteryModifiersStep,
} from '../utils/class-mastery-form-state';

export const useClassMasteryForm = (
  classMasteryId: number | null
): UseClassMasteryFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useClassMasteryFormOptions();
  const {
    class_mastery: existingClassMastery,
    loading: loadingClassMastery,
    error: classMasteryError,
  } = useClassMasteryForEdit(classMasteryId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveClassMastery();

  const [formState, setFormState] = useState<ClassMasteryFormStateDefinition>(
    () => createClassMasteryFormState(null)
  );
  const [errors, setErrors] = useState<ClassMasteryFormErrorsDefinition>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (classMasteryId !== null && !existingClassMastery) {
      return;
    }

    setFormState(createClassMasteryFormState(existingClassMastery));
    setInitialized(true);
  }, [initialized, classMasteryId, existingClassMastery]);

  const updateField: UseClassMasteryFormDefinition['update_field'] = (
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

  const submit = async (): Promise<ClassMasteryFormDefinition | null> => {
    const result = validateClassMasteryForm(formState);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    if (!result.is_valid) {
      return null;
    }

    const payload = buildClassMasteryRequestPayload(formState);

    return save(classMasteryId, payload);
  };

  const validateStep = (stepIndex: number): boolean => {
    const validators = [
      validateClassMasteryIdentityStep,
      validateClassMasteryAttackStep,
      validateClassMasteryModifiersStep,
      validateClassMasteryEvasionStep,
    ];
    const result = validators[stepIndex]?.(formState) ?? {
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
    loading: loadingOptions || loadingClassMastery || !initialized,
    load_error: optionsError ?? classMasteryError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    form_error: formError,
    submit,
    validate_step: validateStep,
  };
};
