import { useEffect, useState } from 'react';

import UseSkillFormDefinition from './definitions/use-skill-form-definition';
import SkillFormDefinition from '../api/definitions/skill-form-definition';
import { useSaveSkill } from '../api/hooks/use-save-skill';
import { useSkillForEdit } from '../api/hooks/use-skill-for-edit';
import { useSkillFormOptions } from '../api/hooks/use-skill-form-options';
import SkillFormErrorsDefinition from '../definitions/skill-form-errors-definition';
import SkillFormStateDefinition from '../definitions/skill-form-state-definition';
import { buildSkillRequestPayload } from '../utils/build-skill-request-payload';
import { createSkillFormState } from '../utils/create-skill-form-state';
import {
  validateSkillBasicStep,
  validateSkillCharacterModifiersStep,
  validateSkillForm,
  validateSkillKingdomModifiersStep,
  validateSkillTimersAndClassStep,
} from '../utils/validate-skill-form';

const STEP_VALIDATORS = [
  validateSkillBasicStep,
  validateSkillCharacterModifiersStep,
  validateSkillTimersAndClassStep,
  validateSkillKingdomModifiersStep,
];

export const useSkillForm = (
  skillId: number | null
): UseSkillFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useSkillFormOptions();
  const {
    skill: existingSkill,
    loading: loadingSkill,
    error: skillError,
  } = useSkillForEdit(skillId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveSkill();

  const [formState, setFormState] = useState<SkillFormStateDefinition>(() =>
    createSkillFormState(null)
  );
  const [errors, setErrors] = useState<SkillFormErrorsDefinition>({});
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (skillId !== null && !existingSkill) {
      return;
    }

    setFormState(createSkillFormState(existingSkill));
    setInitialized(true);
  }, [initialized, skillId, existingSkill]);

  const updateField: UseSkillFormDefinition['update_field'] = (
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

  const validateStep = (stepIndex: number): boolean => {
    const validateCurrentStep = STEP_VALIDATORS[stepIndex];

    if (!validateCurrentStep) {
      return true;
    }

    const result = validateCurrentStep(formState);

    setErrors(result.field_errors);

    return result.is_valid;
  };

  const submit = async (): Promise<SkillFormDefinition | null> => {
    const result = validateSkillForm(formState);

    setErrors(result.field_errors);

    if (!result.is_valid) {
      return null;
    }

    return save(skillId, buildSkillRequestPayload(formState));
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingSkill || !initialized,
    load_error: optionsError ?? skillError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    submit,
    validate_step: validateStep,
  };
};
