import { useEffect, useState } from 'react';

import UseMonsterFormDefinition from './definitions/use-monster-form-definition';
import MonsterFormDefinition from '../api/definitions/monster-form-definition';
import { useMonsterForEdit } from '../api/hooks/use-monster-for-edit';
import { useMonsterFormOptions } from '../api/hooks/use-monster-form-options';
import { useSaveMonster } from '../api/hooks/use-save-monster';
import MonsterFormErrorsDefinition from '../definitions/monster-form-errors-definition';
import MonsterFormStateDefinition from '../definitions/monster-form-state-definition';
import {
  buildMonsterRequestPayload,
  createMonsterFormState,
  validateMonsterCombatStep,
  validateMonsterForm,
  validateMonsterIdentityStep,
  validateMonsterQuestCelestialStep,
  validateMonsterRaidStep,
  validateMonsterSpellStep,
} from '../utils/monster-form-state';

const STEP_VALIDATORS = [
  validateMonsterIdentityStep,
  validateMonsterCombatStep,
  validateMonsterSpellStep,
  validateMonsterQuestCelestialStep,
  validateMonsterRaidStep,
];

export const useMonsterForm = (
  monsterId: number | null
): UseMonsterFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useMonsterFormOptions();
  const {
    monster: existingMonster,
    loading: loadingMonster,
    error: monsterError,
  } = useMonsterForEdit(monsterId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveMonster();

  const [formState, setFormState] = useState<MonsterFormStateDefinition>(() =>
    createMonsterFormState(null)
  );
  const [errors, setErrors] = useState<MonsterFormErrorsDefinition>({});
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (monsterId !== null && !existingMonster) {
      return;
    }

    setFormState(createMonsterFormState(existingMonster));
    setInitialized(true);
  }, [initialized, monsterId, existingMonster]);

  const updateField: UseMonsterFormDefinition['update_field'] = (
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

    const validationErrors = validateCurrentStep(formState);

    setErrors(validationErrors);

    return Object.keys(validationErrors).length === 0;
  };

  const submit = async (): Promise<MonsterFormDefinition | null> => {
    const validationErrors = validateMonsterForm(formState);

    setErrors(validationErrors);

    if (Object.keys(validationErrors).length > 0) {
      return null;
    }

    const payload = buildMonsterRequestPayload(formState);

    return save(monsterId, payload);
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingMonster || !initialized,
    load_error: optionsError ?? monsterError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    submit,
    validate_step: validateStep,
  };
};
