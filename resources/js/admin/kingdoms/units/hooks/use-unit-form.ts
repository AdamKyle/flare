import { useEffect, useState } from 'react';

import UseUnitFormDefinition from './definitions/use-unit-form-definition';
import UnitFormDefinition from '../api/definitions/unit-form-definition';
import { useSaveUnit } from '../api/hooks/use-save-unit';
import { useUnitForEdit } from '../api/hooks/use-unit-for-edit';
import UnitFormErrorsDefinition from '../definitions/unit-form-errors-definition';
import UnitFormStateDefinition from '../definitions/unit-form-state-definition';
import { buildUnitRequestPayload } from '../utils/build-unit-request-payload';
import { createUnitFormState } from '../utils/create-unit-form-state';
import {
  validateUnitBasicAndCombatStep,
  validateUnitForm,
  validateUnitResourceCostsStep,
} from '../utils/validate-unit-form';

const STEP_VALIDATORS = [
  validateUnitBasicAndCombatStep,
  validateUnitResourceCostsStep,
];

export const useUnitForm = (unitId: number | null): UseUnitFormDefinition => {
  const {
    unit: existingUnit,
    loading: loadingUnit,
    error: unitError,
  } = useUnitForEdit(unitId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveUnit();

  const [formState, setFormState] = useState<UnitFormStateDefinition>(() =>
    createUnitFormState(null)
  );
  const [errors, setErrors] = useState<UnitFormErrorsDefinition>({});
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (unitId !== null && !existingUnit) {
      return;
    }

    setFormState(createUnitFormState(existingUnit));
    setInitialized(true);
  }, [initialized, unitId, existingUnit]);

  const updateField: UseUnitFormDefinition['update_field'] = (field, value) => {
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

  const submit = async (): Promise<UnitFormDefinition | null> => {
    const result = validateUnitForm(formState);

    setErrors(result.field_errors);

    if (!result.is_valid) {
      return null;
    }

    return save(unitId, buildUnitRequestPayload(formState));
  };

  return {
    form_state: formState,
    update_field: updateField,
    loading: loadingUnit || !initialized,
    load_error: unitError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    submit,
    validate_step: validateStep,
  };
};
