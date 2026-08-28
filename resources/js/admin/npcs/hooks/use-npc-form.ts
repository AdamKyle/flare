import { useEffect, useState } from 'react';

import UseNpcFormDefinition from './definitions/use-npc-form-definition';
import { useNpcValidation } from './use-npc-validation';
import { useNpc } from '../api/hooks/use-npc';
import { useNpcFormOptions } from '../api/hooks/use-npc-form-options';
import { useSaveNpc } from '../api/hooks/use-save-npc';
import NpcDefinition from '../api/definitions/npc-definition';
import NpcFormState from '../types/npc-form-state';
import { buildNpcRequest } from '../utils/build-npc-request';
import { createNpcFormState } from '../utils/create-npc-form-state';

export const useNpcForm = (
  gameMapId: number,
  npcId: number | null,
  initialX: number | null,
  initialY: number | null,
  onSaved: (npc: NpcDefinition) => void
): UseNpcFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useNpcFormOptions(gameMapId);
  const {
    npc,
    loading: loadingNpc,
    error: npcError,
  } = useNpc(gameMapId, npcId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveNpc();
  const {
    errors,
    validate_step: validateStep,
    validate_all: validateAll,
    validate_field: validateField,
    clear_errors: clearErrors,
  } = useNpcValidation(formOptions);

  const [formState, setFormState] = useState<NpcFormState>(() =>
    createNpcFormState(null, initialX, initialY)
  );
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (npcId !== null && !npc) {
      return;
    }

    setFormState(createNpcFormState(npc, initialX, initialY));
    setInitialized(true);
  }, [initialized, npcId, npc, initialX, initialY]);

  const updateField = <K extends keyof NpcFormState>(
    field: K,
    value: NpcFormState[K]
  ): void => {
    const nextState = { ...formState, [field]: value };

    setFormState(nextState);
    validateField(field, nextState);
    clearServerFieldError(field);
  };

  const requestNext = (stepIndex: number): boolean =>
    validateStep(stepIndex, formState);

  const submit = async (): Promise<boolean> => {
    if (!validateAll(formState)) {
      return false;
    }

    const request = buildNpcRequest(formState);
    const result = await save(gameMapId, npcId, request);

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
    loading: loadingOptions || loadingNpc || !initialized,
    load_error: optionsError ?? npcError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    request_next: requestNext,
    submit,
    clear_errors: clearErrors,
  };
};
