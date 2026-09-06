import { useEffect, useState } from 'react';

import UseClassFormDefinition from './definitions/use-class-form-definition';
import ClassFormDefinition from '../api/definitions/class-form-definition';
import { useClassForEdit } from '../api/hooks/use-class-for-edit';
import { useClassFormOptions } from '../api/hooks/use-class-form-options';
import { useSaveClass } from '../api/hooks/use-save-class';
import ClassFormErrorsDefinition from '../definitions/class-form-errors-definition';
import ClassFormStateDefinition from '../definitions/class-form-state-definition';
import {
  buildClassRequestPayload,
  createClassFormState,
  validateClassAttributesStep,
  validateClassBasicStep,
  validateClassCombatStep,
  validateClassForm,
  validateClassUnlockStep,
} from '../utils/class-form-state';

export const useClassForm = (
  classId: number | null
): UseClassFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useClassFormOptions();
  const {
    game_class: existingClass,
    loading: loadingClass,
    error: classError,
  } = useClassForEdit(classId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveClass();

  const [formState, setFormState] = useState<ClassFormStateDefinition>(() =>
    createClassFormState(null)
  );
  const [errors, setErrors] = useState<ClassFormErrorsDefinition>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (classId !== null && !existingClass) {
      return;
    }

    setFormState(createClassFormState(existingClass));
    setInitialized(true);
  }, [initialized, classId, existingClass]);

  const updateField: UseClassFormDefinition['update_field'] = (
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

  const stepValidators = [
    validateClassBasicStep,
    validateClassAttributesStep,
    validateClassCombatStep,
    (state: ClassFormStateDefinition) =>
      validateClassUnlockStep(state, classId),
  ];

  const validateStep = (stepIndex: number): boolean => {
    const validateCurrentStep = stepValidators[stepIndex];

    if (!validateCurrentStep) {
      return true;
    }

    const result = validateCurrentStep(formState);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    return result.is_valid;
  };

  const submit = async (): Promise<ClassFormDefinition | null> => {
    const result = validateClassForm(formState, classId);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    if (!result.is_valid) {
      return null;
    }

    const payload = buildClassRequestPayload(formState);

    return save(classId, payload);
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingClass || !initialized,
    load_error: optionsError ?? classError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    form_error: formError,
    submit,
    validate_step: validateStep,
  };
};
