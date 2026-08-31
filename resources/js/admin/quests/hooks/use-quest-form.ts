import { useEffect, useState } from 'react';

import UseQuestFormDefinition from './definitions/use-quest-form-definition';
import QuestFormDefinition from '../api/definitions/quest-form-definition';
import { useQuestForEdit } from '../api/hooks/use-quest-for-edit';
import { useQuestFormOptions } from '../api/hooks/use-quest-form-options';
import { useSaveQuest } from '../api/hooks/use-save-quest';
import QuestFormErrorsDefinition from '../definitions/quest-form-errors-definition';
import QuestFormStateDefinition from '../definitions/quest-form-state-definition';
import {
  buildQuestRequestPayload,
  createQuestFormState,
  validateQuestForm,
  validateQuestRequirementsStep,
  validateQuestRewardsStep,
  validateQuestStoryStep,
  validateQuestStructureStep,
} from '../utils/quest-form-state';

const STEP_VALIDATORS = [
  validateQuestStoryStep,
  validateQuestStructureStep,
  validateQuestRequirementsStep,
  validateQuestRewardsStep,
];

export const useQuestForm = (
  questId: number | null,
  parentQuestId: number | null = null
): UseQuestFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useQuestFormOptions();
  const {
    quest: existingQuest,
    loading: loadingQuest,
    error: questError,
  } = useQuestForEdit(questId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveQuest();

  const [formState, setFormState] = useState<QuestFormStateDefinition>(() =>
    createQuestFormState(null)
  );
  const [errors, setErrors] = useState<QuestFormErrorsDefinition>({});
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (questId !== null && !existingQuest) {
      return;
    }

    const initialState = createQuestFormState(existingQuest);

    if (questId === null && parentQuestId !== null) {
      initialState.parent_quest_id = parentQuestId;
    }

    setFormState(initialState);
    setInitialized(true);
  }, [initialized, questId, existingQuest, parentQuestId]);

  const updateField: UseQuestFormDefinition['update_field'] = (
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

    const validationErrors = validateCurrentStep(formState);

    setErrors(validationErrors);

    return Object.keys(validationErrors).length === 0;
  };

  const submit = async (): Promise<QuestFormDefinition | null> => {
    const validationErrors = validateQuestForm(formState);

    setErrors(validationErrors);

    if (Object.keys(validationErrors).length > 0) {
      return null;
    }

    const payload = buildQuestRequestPayload(formState);

    return save(questId, payload);
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingQuest || !initialized,
    load_error: optionsError ?? questError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    submit,
    validate_step: validateStep,
  };
};
