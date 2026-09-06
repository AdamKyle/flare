import { useEffect, useState } from 'react';

import UseLocationGemFormDefinition from './definitions/use-location-gem-form-definition';
import LocationGemFormDefinition from '../api/definitions/location-gem-form-definition';
import { useLocationGemForEdit } from '../api/hooks/use-location-gem-for-edit';
import { useLocationGemFormOptions } from '../api/hooks/use-location-gem-form-options';
import { useSaveLocationGem } from '../api/hooks/use-save-location-gem';
import LocationGemFormErrorsDefinition from '../definitions/location-gem-form-errors-definition';
import LocationGemFormStateDefinition from '../definitions/location-gem-form-state-definition';
import {
  buildLocationGemRequestPayload,
  createLocationGemFormState,
  validateLocationGemForm,
  validateLocationGemStep,
} from '../utils/location-gem-form-state';

export const useLocationGemForm = (
  locationGemId: number | null
): UseLocationGemFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useLocationGemFormOptions();
  const {
    location_gem: existingLocationGem,
    loading: loadingLocationGem,
    error: locationGemError,
  } = useLocationGemForEdit(locationGemId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveLocationGem();

  const [formState, setFormState] = useState<LocationGemFormStateDefinition>(
    () => createLocationGemFormState(null)
  );
  const [errors, setErrors] = useState<LocationGemFormErrorsDefinition>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (locationGemId !== null && !existingLocationGem) {
      return;
    }

    setFormState(createLocationGemFormState(existingLocationGem));
    setInitialized(true);
  }, [initialized, locationGemId, existingLocationGem]);

  const updateField: UseLocationGemFormDefinition['update_field'] = (
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

  const submit = async (): Promise<LocationGemFormDefinition | null> => {
    const result = validateLocationGemForm(formState);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    if (!result.is_valid) {
      return null;
    }

    const payload = buildLocationGemRequestPayload(formState);

    return save(locationGemId, payload);
  };

  const validateStep = (stepIndex: number): boolean => {
    const result = validateLocationGemStep(formState, stepIndex);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    return result.is_valid;
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingLocationGem || !initialized,
    load_error: optionsError ?? locationGemError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    form_error: formError,
    submit,
    validate_step: validateStep,
  };
};
