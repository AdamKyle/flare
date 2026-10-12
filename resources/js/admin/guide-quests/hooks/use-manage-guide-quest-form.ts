import { useMemo, useState } from 'react';

import UseManageGuideQuestFormDefinition from './definitions/use-manage-guide-quest-form-definition';
import UseManageGuideQuestFormParams from './definitions/use-manage-guide-quest-form-params';
import { useSaveGuideQuest } from '../api/hooks/use-save-guide-quest';
import { useStoreGuideQuestWalkthrough } from '../api/hooks/use-store-guide-quest-walkthrough';
import GuideQuestFormErrorsDefinition from '../definitions/guide-quest-form-errors-definition';
import GuideQuestFormStateDefinition from '../definitions/guide-quest-form-state-definition';
import {
  createEmptyGuideQuestFormState,
  createGuideQuestFormState,
} from '../utils/guide-quest-form-state';
import {
  validateGuideQuestForm,
  validateGuideQuestStep,
} from '../utils/guide-quest-form-validation';

export const useManageGuideQuestForm = ({
  guide_quest_id: guideQuestId,
  data,
  updateGuideQuest,
}: UseManageGuideQuestFormParams): UseManageGuideQuestFormDefinition => {
  const [formState, setFormState] = useState<GuideQuestFormStateDefinition>(
    () => ({
      ...(data.guide_quest
        ? createGuideQuestFormState(data.guide_quest)
        : createEmptyGuideQuestFormState()),
      walkthrough_steps: data.walkthrough_steps,
    })
  );
  const [clientErrors, setClientErrors] =
    useState<GuideQuestFormErrorsDefinition>({});
  const {
    saving,
    error: contentError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveGuideQuest();
  const {
    error: walkthroughError,
    loading: walkthroughSaving,
    storeWalkthrough,
  } = useStoreGuideQuestWalkthrough();
  const fieldErrors = useMemo(
    () => ({ ...clientErrors, ...serverFieldErrors }),
    [clientErrors, serverFieldErrors]
  );

  const updateField: UseManageGuideQuestFormDefinition['update_field'] = (
    field,
    value
  ) => {
    setFormState((previous) => ({ ...previous, [field]: value }));
    clearServerFieldError(field);
    setClientErrors((previous) => {
      if (!(field in previous)) {
        return previous;
      }

      const next = { ...previous };
      delete next[field];
      return next;
    });
  };

  const updateWalkthroughSteps: UseManageGuideQuestFormDefinition['update_walkthrough_steps'] =
    (steps) => updateField('walkthrough_steps', steps);

  const validateStep = (stepIndex: number): boolean => {
    const errors = validateGuideQuestStep(stepIndex, formState);
    setClientErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const submit = async () => {
    const validationErrors = validateGuideQuestForm(formState);
    setClientErrors(validationErrors);

    if (Object.keys(validationErrors).length > 0) {
      return null;
    }

    const savedGuideQuest = await save(guideQuestId, formState);

    if (!savedGuideQuest) {
      return null;
    }

    const walkthroughSaved = await storeWalkthrough(
      savedGuideQuest.id,
      formState.walkthrough_steps
    );

    if (!walkthroughSaved) {
      return null;
    }

    updateGuideQuest({
      ...data,
      guide_quest: {
        ...savedGuideQuest,
        walkthrough_steps: formState.walkthrough_steps,
      },
      walkthrough_steps: formState.walkthrough_steps,
    });

    return savedGuideQuest;
  };

  return {
    form_state: formState,
    field_errors: fieldErrors,
    update_field: updateField,
    update_walkthrough_steps: updateWalkthroughSteps,
    validate_step: validateStep,
    submit,
    is_saving: saving || walkthroughSaving,
    save_error:
      contentError ??
      (walkthroughError === null ? null : { message: walkthroughError }),
  };
};
