import { useEffect, useState } from 'react';

import UsePassiveSkillFormDefinition from './definitions/use-passive-skill-form-definition';
import PassiveSkillFormDefinition from '../api/definitions/passive-skill-form-definition';
import { usePassiveSkillForEdit } from '../api/hooks/use-passive-skill-for-edit';
import { usePassiveSkillFormOptions } from '../api/hooks/use-passive-skill-form-options';
import { useSavePassiveSkill } from '../api/hooks/use-save-passive-skill';
import PassiveSkillFormErrorsDefinition from '../definitions/passive-skill-form-errors-definition';
import PassiveSkillFormStateDefinition from '../definitions/passive-skill-form-state-definition';
import PassiveSkillValidationResultDefinition from '../definitions/passive-skill-validation-result-definition';
import { buildPassiveSkillRequestPayload } from '../utils/build-passive-skill-request-payload';
import { createPassiveSkillFormState } from '../utils/create-passive-skill-form-state';
import {
  validatePassiveSkillBasicStep,
  validatePassiveSkillBonusesStep,
  validatePassiveSkillForm,
  validatePassiveSkillTreeStep,
} from '../utils/validate-passive-skill-form';

export const usePassiveSkillForm = (
  passiveSkillId: number | null
): UsePassiveSkillFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = usePassiveSkillFormOptions();
  const {
    passive_skill: existingPassiveSkill,
    loading: loadingPassiveSkill,
    error: passiveSkillError,
  } = usePassiveSkillForEdit(passiveSkillId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSavePassiveSkill();

  const [formState, setFormState] = useState<PassiveSkillFormStateDefinition>(
    () => createPassiveSkillFormState(null)
  );
  const [errors, setErrors] = useState<PassiveSkillFormErrorsDefinition>({});
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (passiveSkillId !== null && !existingPassiveSkill) {
      return;
    }

    setFormState(createPassiveSkillFormState(existingPassiveSkill));
    setInitialized(true);
  }, [initialized, passiveSkillId, existingPassiveSkill]);

  const updateField: UsePassiveSkillFormDefinition['update_field'] = (
    field,
    value
  ) => {
    setFormState((previous) => ({ ...previous, [field]: value }));
    clearServerFieldError(field);
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
    validatePassiveSkillBasicStep,
    validatePassiveSkillBonusesStep,
    (state: PassiveSkillFormStateDefinition) =>
      validatePassiveSkillTreeStep(state, passiveSkillId),
  ];

  const applyValidationResult = (
    result: PassiveSkillValidationResultDefinition
  ): boolean => {
    setErrors(result.field_errors);

    return result.is_valid;
  };

  const validateStep = (stepIndex: number): boolean => {
    const validateCurrentStep = stepValidators[stepIndex];

    if (!validateCurrentStep) {
      return true;
    }

    return applyValidationResult(validateCurrentStep(formState));
  };

  const submit = async (): Promise<PassiveSkillFormDefinition | null> => {
    const isValid = applyValidationResult(
      validatePassiveSkillForm(formState, passiveSkillId)
    );

    if (!isValid) {
      return null;
    }

    return save(passiveSkillId, buildPassiveSkillRequestPayload(formState));
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingPassiveSkill || !initialized,
    load_error: optionsError ?? passiveSkillError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    submit,
    validate_step: validateStep,
  };
};
