import { useEffect, useState } from 'react';

import UseGameMapFormDefinition from './definitions/use-game-map-form-definition';
import { useGameMapForEdit } from '../api/hooks/use-game-map-for-edit';
import { useGameMapFormOptions } from '../api/hooks/use-game-map-form-options';
import { useSaveGameMap } from '../api/hooks/use-save-game-map';
import GameMapFormErrorsDefinition from '../definitions/game-map-form-errors-definition';
import GameMapFormResponseDefinition from '../definitions/game-map-form-response-definition';
import GameMapFormStateDefinition from '../definitions/game-map-form-state-definition';
import { buildGameMapFormData } from '../utils/build-game-map-form-data';
import { createGameMapFormState } from '../utils/create-game-map-form-state';
import { isGameMapValidatableField } from '../utils/is-game-map-validatable-field';
import { validateAllGameMapSteps } from '../utils/validate-all-game-map-steps';
import { validateGameMapStep } from '../utils/validate-game-map-step';

export const useGameMapForm = (
  gameMapId: number | null
): UseGameMapFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useGameMapFormOptions();
  const {
    game_map: existingGameMap,
    loading: loadingGameMap,
    error: gameMapError,
  } = useGameMapForEdit(gameMapId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveGameMap();

  const [formState, setFormState] = useState<GameMapFormStateDefinition>(() =>
    createGameMapFormState(null)
  );
  const [errors, setErrors] = useState<GameMapFormErrorsDefinition>({});
  const [initialized, setInitialized] = useState(false);
  const [currentMapUrl, setCurrentMapUrl] = useState<string | null>(null);

  const isCreate = gameMapId === null;

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (gameMapId !== null && !existingGameMap) {
      return;
    }

    setFormState(createGameMapFormState(existingGameMap));
    setCurrentMapUrl(existingGameMap?.map_url ?? null);
    setInitialized(true);
  }, [initialized, gameMapId, existingGameMap]);

  const validateField = (
    field: keyof GameMapFormErrorsDefinition,
    state: GameMapFormStateDefinition
  ): void => {
    const stepErrors = validateAllGameMapSteps(state, isCreate, formOptions);

    setErrors((previous) => {
      const next = { ...previous };

      if (stepErrors[field]) {
        next[field] = stepErrors[field];
      } else {
        delete next[field];
      }

      return next;
    });
  };

  const updateField = <K extends keyof GameMapFormStateDefinition>(
    field: K,
    value: GameMapFormStateDefinition[K]
  ): void => {
    const nextState: GameMapFormStateDefinition = {
      ...formState,
      [field]: value,
    };

    if (field === 'map') {
      nextState.replacement_image_acknowledged = false;
    }

    setFormState(nextState);

    if (isGameMapValidatableField(field)) {
      validateField(field, nextState);
      clearServerFieldError(field);
    }
  };

  const requestNext = (stepIndex: number): boolean => {
    const stepErrors = validateGameMapStep(
      stepIndex,
      formState,
      isCreate,
      formOptions
    );

    setErrors((previous) => ({ ...previous, ...stepErrors }));

    return Object.keys(stepErrors).length === 0;
  };

  const submit = async (): Promise<GameMapFormResponseDefinition | null> => {
    const combinedErrors = validateAllGameMapSteps(
      formState,
      isCreate,
      formOptions
    );

    setErrors(combinedErrors);

    if (Object.keys(combinedErrors).length > 0) {
      return null;
    }

    const formData = buildGameMapFormData(formState, gameMapId);

    return save(gameMapId, formData);
  };

  const clearErrors = (): void => {
    setErrors({});
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    current_map_url: currentMapUrl,
    loading: loadingOptions || loadingGameMap || !initialized,
    load_error: optionsError ?? gameMapError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    request_next: requestNext,
    submit,
    clear_errors: clearErrors,
  };
};
