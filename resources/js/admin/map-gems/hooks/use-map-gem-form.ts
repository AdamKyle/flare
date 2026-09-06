import { useEffect, useState } from 'react';

import UseMapGemFormDefinition from './definitions/use-map-gem-form-definition';
import MapGemFormDefinition from '../api/definitions/map-gem-form-definition';
import { useMapGemForEdit } from '../api/hooks/use-map-gem-for-edit';
import { useMapGemFormOptions } from '../api/hooks/use-map-gem-form-options';
import { useSaveMapGem } from '../api/hooks/use-save-map-gem';
import MapGemFormErrorsDefinition from '../definitions/map-gem-form-errors-definition';
import MapGemFormStateDefinition from '../definitions/map-gem-form-state-definition';
import {
  buildMapGemRequestPayload,
  createMapGemFormState,
  validateMapGemForm,
  validateMapGemStep,
} from '../utils/map-gem-form-state';

export const useMapGemForm = (
  mapGemId: number | null
): UseMapGemFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useMapGemFormOptions();
  const {
    map_gem: existingMapGem,
    loading: loadingMapGem,
    error: mapGemError,
  } = useMapGemForEdit(mapGemId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveMapGem();

  const [formState, setFormState] = useState<MapGemFormStateDefinition>(() =>
    createMapGemFormState(null)
  );
  const [errors, setErrors] = useState<MapGemFormErrorsDefinition>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (mapGemId !== null && !existingMapGem) {
      return;
    }

    setFormState(createMapGemFormState(existingMapGem));
    setInitialized(true);
  }, [initialized, mapGemId, existingMapGem]);

  const updateField: UseMapGemFormDefinition['update_field'] = (
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

  const submit = async (): Promise<MapGemFormDefinition | null> => {
    const result = validateMapGemForm(formState);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    if (!result.is_valid) {
      return null;
    }

    const payload = buildMapGemRequestPayload(formState);

    return save(mapGemId, payload);
  };

  const validateStep = (stepIndex: number): boolean => {
    const result = validateMapGemStep(formState, stepIndex);

    setErrors(result.field_errors);
    setFormError(result.form_error);

    return result.is_valid;
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingMapGem || !initialized,
    load_error: optionsError ?? mapGemError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    form_error: formError,
    submit,
    validate_step: validateStep,
  };
};
