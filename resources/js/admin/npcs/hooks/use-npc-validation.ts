import { useState } from 'react';

import UseNpcValidationDefinition from './definitions/use-npc-validation-definition';
import NpcFormOptionsDefinition from '../api/definitions/npc-form-options-definition';
import NpcFormErrors from '../types/npc-form-errors';
import NpcFormState from '../types/npc-form-state';

export const useNpcValidation = (
  formOptions: NpcFormOptionsDefinition | null
): UseNpcValidationDefinition => {
  const [errors, setErrors] = useState<NpcFormErrors>({});

  const validateDetailsStep = (state: NpcFormState): NpcFormErrors => {
    const stepErrors: NpcFormErrors = {};

    if (state.real_name.trim() === '') {
      stepErrors.real_name = 'Enter an Npc name.';
    }

    if (
      state.type === null ||
      !formOptions?.npc_types.some((option) => option.value === state.type)
    ) {
      stepErrors.type = 'Select a valid Npc type.';
    }

    return stepErrors;
  };

  const validatePositionStep = (state: NpcFormState): NpcFormErrors => {
    const stepErrors: NpcFormErrors = {};

    if (
      state.x_position === null ||
      !formOptions?.coordinates.x.includes(state.x_position)
    ) {
      stepErrors.x_position = 'Select a valid X coordinate.';
    }

    if (
      state.y_position === null ||
      !formOptions?.coordinates.y.includes(state.y_position)
    ) {
      stepErrors.y_position = 'Select a valid Y coordinate.';
    }

    return stepErrors;
  };

  const resolveErrors = (state: NpcFormState): NpcFormErrors => ({
    ...validateDetailsStep(state),
    ...validatePositionStep(state),
  });

  const validateStep = (stepIndex: number, state: NpcFormState): boolean => {
    const stepErrors =
      stepIndex === 0
        ? validateDetailsStep(state)
        : validatePositionStep(state);

    setErrors((previous) => ({ ...previous, ...stepErrors }));

    return Object.keys(stepErrors).length === 0;
  };

  const validateAll = (state: NpcFormState): boolean => {
    const nextErrors = resolveErrors(state);

    setErrors(nextErrors);

    return Object.keys(nextErrors).length === 0;
  };

  const validateField = (
    field: keyof NpcFormErrors,
    state: NpcFormState
  ): void => {
    const fieldErrors = resolveErrors(state);

    setErrors((previous) => {
      const next = { ...previous };

      if (fieldErrors[field]) {
        next[field] = fieldErrors[field];
      } else {
        delete next[field];
      }

      return next;
    });
  };

  const clearErrors = (): void => {
    setErrors({});
  };

  return {
    errors,
    validate_step: validateStep,
    validate_all: validateAll,
    validate_field: validateField,
    clear_errors: clearErrors,
  };
};
