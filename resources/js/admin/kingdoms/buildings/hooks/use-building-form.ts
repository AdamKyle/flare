import { useEffect, useState } from 'react';

import UseBuildingFormDefinition from './definitions/use-building-form-definition';
import BuildingFormDefinition from '../api/definitions/building-form-definition';
import { useBuildingForEdit } from '../api/hooks/use-building-for-edit';
import { useBuildingFormOptions } from '../api/hooks/use-building-form-options';
import { useSaveBuilding } from '../api/hooks/use-save-building';
import BuildingFormErrorsDefinition from '../definitions/building-form-errors-definition';
import BuildingFormStateDefinition from '../definitions/building-form-state-definition';
import { buildBuildingRequestPayload } from '../utils/build-building-request-payload';
import { createBuildingFormState } from '../utils/create-building-form-state';
import {
  validateBuildingBasicStep,
  validateBuildingForm,
  validateBuildingUnitRecruitmentStep,
  validateBuildingUpgradeCostsStep,
  validateBuildingUpgradeEffectsStep,
} from '../utils/validate-building-form';

const STEP_VALIDATORS = [
  validateBuildingBasicStep,
  validateBuildingUpgradeCostsStep,
  validateBuildingUpgradeEffectsStep,
  validateBuildingUnitRecruitmentStep,
];

export const useBuildingForm = (
  buildingId: number | null
): UseBuildingFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useBuildingFormOptions();
  const {
    building: existingBuilding,
    loading: loadingBuilding,
    error: buildingError,
  } = useBuildingForEdit(buildingId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveBuilding();

  const [formState, setFormState] = useState<BuildingFormStateDefinition>(() =>
    createBuildingFormState(null)
  );
  const [errors, setErrors] = useState<BuildingFormErrorsDefinition>({});
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (buildingId !== null && !existingBuilding) {
      return;
    }

    setFormState(createBuildingFormState(existingBuilding));
    setInitialized(true);
  }, [initialized, buildingId, existingBuilding]);

  const updateField: UseBuildingFormDefinition['update_field'] = (
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

  const submit = async (): Promise<BuildingFormDefinition | null> => {
    const result = validateBuildingForm(formState);

    setErrors(result.field_errors);

    if (!result.is_valid) {
      return null;
    }

    return save(buildingId, buildBuildingRequestPayload(formState));
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingBuilding || !initialized,
    load_error: optionsError ?? buildingError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    submit,
    validate_step: validateStep,
  };
};
