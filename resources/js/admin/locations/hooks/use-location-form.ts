import { useEffect, useState } from 'react';

import UseLocationFormDefinition from './definitions/use-location-form-definition';
import { useLocationValidation } from './use-location-validation';
import LocationDefinition from '../api/definitions/location-definition';
import { useLocation } from '../api/hooks/use-location';
import { useLocationFormOptions } from '../api/hooks/use-location-form-options';
import { useSaveLocation } from '../api/hooks/use-save-location';
import LocationFormState from '../types/location-form-state';
import { buildLocationRequest } from '../utils/build-location-request';
import { createLocationFormState } from '../utils/create-location-form-state';
import { isLocationValidatableField } from '../utils/is-location-validatable-field';

export const useLocationForm = (
  gameMapId: number,
  locationId: number | null,
  initialX: number | null,
  initialY: number | null,
  onSaved: (location: LocationDefinition) => void
): UseLocationFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useLocationFormOptions(gameMapId);
  const {
    location,
    loading: loadingLocation,
    error: locationError,
  } = useLocation(gameMapId, locationId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveLocation();
  const {
    errors,
    validate_step: validateStep,
    validate_all: validateAll,
    validate_field: validateField,
    clear_errors: clearErrors,
  } = useLocationValidation(formOptions);

  const [formState, setFormState] = useState<LocationFormState>(() =>
    createLocationFormState(null, initialX, initialY)
  );
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (locationId !== null && !location) {
      return;
    }

    setFormState(createLocationFormState(location, initialX, initialY));
    setInitialized(true);
  }, [initialized, locationId, location, initialX, initialY]);

  const updateField = <K extends keyof LocationFormState>(
    field: K,
    value: LocationFormState[K]
  ): void => {
    const nextState = { ...formState, [field]: value };

    setFormState(nextState);

    if (isLocationValidatableField(field)) {
      validateField(field, nextState);
      clearServerFieldError(field);
    }
  };

  const requestNext = (stepIndex: number): boolean =>
    validateStep(stepIndex, formState);

  const submit = async (): Promise<boolean> => {
    if (!validateAll(formState)) {
      return false;
    }

    const request = buildLocationRequest(formState);
    const result = await save(gameMapId, locationId, request);

    if (!result) {
      return false;
    }

    onSaved(result);

    return true;
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingLocation || !initialized,
    load_error: optionsError ?? locationError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    request_next: requestNext,
    submit,
    clear_errors: clearErrors,
  };
};
