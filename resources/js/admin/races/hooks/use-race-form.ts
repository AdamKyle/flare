import { useEffect, useState } from 'react';

import UseRaceFormDefinition from './definitions/use-race-form-definition';
import RaceDefinition from '../api/definitions/race-definition';
import { useRaceForEdit } from '../api/hooks/use-race-for-edit';
import { useSaveRace } from '../api/hooks/use-save-race';
import RaceFormErrorsDefinition from '../definitions/race-form-errors-definition';
import RaceFormStateDefinition from '../definitions/race-form-state-definition';
import {
  buildRaceFormData,
  createRaceFormState,
  validateRaceForm,
} from '../utils/race-form-state';

export const useRaceForm = (raceId: number | null): UseRaceFormDefinition => {
  const {
    race: existingRace,
    loading: loadingRace,
    error: loadError,
  } = useRaceForEdit(raceId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveRace();

  const [formState, setFormState] = useState<RaceFormStateDefinition>(() =>
    createRaceFormState(null)
  );
  const [errors, setErrors] = useState<RaceFormErrorsDefinition>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (raceId !== null && !existingRace) {
      return;
    }

    setFormState(createRaceFormState(existingRace));
    setInitialized(true);
  }, [initialized, raceId, existingRace]);

  const updateField: UseRaceFormDefinition['update_field'] = (field, value) => {
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

  const submit = async (): Promise<RaceDefinition | null> => {
    const result = validateRaceForm(formState);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    if (!result.is_valid) {
      return null;
    }

    const formData = buildRaceFormData(formState, raceId);

    return save(raceId, formData);
  };

  const validateStep = (): boolean => {
    const result = validateRaceForm(formState);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    return result.is_valid;
  };

  return {
    form_state: formState,
    update_field: updateField,
    loading: loadingRace || !initialized,
    load_error: loadError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    form_error: formError,
    submit,
    validate_step: validateStep,
  };
};
