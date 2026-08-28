import { useState } from 'react';

import UseLocationValidationDefinition from './definitions/use-location-validation-definition';
import LocationFormOptionsDefinition from '../api/definitions/location-form-options-definition';
import LocationFormErrors from '../types/location-form-errors';
import LocationFormState from '../types/location-form-state';
import { isValidOptionalInteger } from '../utils/is-valid-optional-integer';

export const useLocationValidation = (
  formOptions: LocationFormOptionsDefinition | null
): UseLocationValidationDefinition => {
  const [errors, setErrors] = useState<LocationFormErrors>({});

  const validateBasicStep = (state: LocationFormState): LocationFormErrors => {
    const stepErrors: LocationFormErrors = {};

    if (state.name.trim() === '') {
      stepErrors.name = 'Enter a Location name.';
    }

    if (state.description.trim() === '') {
      stepErrors.description = 'Enter a Location description.';
    }

    if (state.x === null || !formOptions?.coordinates.x.includes(state.x)) {
      stepErrors.x = 'Select a valid X coordinate.';
    }

    if (state.y === null || !formOptions?.coordinates.y.includes(state.y)) {
      stepErrors.y = 'Select a valid Y coordinate.';
    }

    return stepErrors;
  };

  const validateRulesStep = (state: LocationFormState): LocationFormErrors => {
    const stepErrors: LocationFormErrors = {};

    if (
      state.type !== null &&
      !formOptions?.location_types.some((option) => option.value === state.type)
    ) {
      stepErrors.type = 'Select a valid Location type.';
    }

    if (
      state.pin_css_class !== null &&
      !formOptions?.special_pins.some(
        (option) => option.value === state.pin_css_class
      )
    ) {
      stepErrors.pin_css_class = 'Select a valid map pin.';
    }

    if (!isValidOptionalInteger(state.hours_to_drop)) {
      stepErrors.hours_to_drop =
        'Hours until quest-item drop must be zero or greater.';
    }

    if (!isValidOptionalInteger(state.minutes_between_delve_fights)) {
      stepErrors.minutes_between_delve_fights =
        'Minutes between Delve fights must be zero or greater.';
    }

    return stepErrors;
  };

  const validateStep = (
    stepIndex: number,
    state: LocationFormState
  ): boolean => {
    const stepErrors =
      stepIndex === 0 ? validateBasicStep(state) : validateRulesStep(state);

    setErrors((previous) => ({ ...previous, ...stepErrors }));

    return Object.keys(stepErrors).length === 0;
  };

  const validateAll = (state: LocationFormState): boolean => {
    const combinedErrors = {
      ...validateBasicStep(state),
      ...validateRulesStep(state),
    };

    setErrors(combinedErrors);

    return Object.keys(combinedErrors).length === 0;
  };

  const clearErrors = (): void => {
    setErrors({});
  };

  const validateField = (
    field: keyof LocationFormErrors,
    state: LocationFormState
  ): void => {
    const stepErrors = {
      ...validateBasicStep(state),
      ...validateRulesStep(state),
    };

    setErrors((previous) => {
      const fieldError = stepErrors[field];
      const next = { ...previous };

      if (fieldError) {
        next[field] = fieldError;
      } else {
        delete next[field];
      }

      return next;
    });
  };

  return {
    errors,
    validate_step: validateStep,
    validate_all: validateAll,
    validate_field: validateField,
    clear_errors: clearErrors,
  };
};
