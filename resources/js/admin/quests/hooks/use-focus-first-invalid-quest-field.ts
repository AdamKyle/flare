import { useEffect, useRef, useState } from 'react';

import UseFocusFirstInvalidQuestFieldDefinition from './definitions/use-focus-first-invalid-quest-field-definition';
import { useQuestFieldFocus } from './use-quest-field-focus';
import QuestFormErrorsDefinition from '../definitions/quest-form-errors-definition';
import { resolveFirstInvalidQuestField } from '../utils/resolve-first-invalid-quest-field';

export const useFocusFirstInvalidQuestField = (
  fieldErrors: QuestFormErrorsDefinition,
  currentStepIndex: number
): UseFocusFirstInvalidQuestFieldDefinition => {
  const [attemptToken, setAttemptToken] = useState(0);
  const consumedAttemptTokenRef = useRef(0);
  const [pendingFieldId, setPendingFieldId] = useState<string | null>(null);

  useEffect(() => {
    if (
      attemptToken === 0 ||
      attemptToken === consumedAttemptTokenRef.current
    ) {
      return;
    }

    consumedAttemptTokenRef.current = attemptToken;

    setPendingFieldId(
      resolveFirstInvalidQuestField(fieldErrors, currentStepIndex)
    );
  }, [attemptToken, fieldErrors, currentStepIndex]);

  useQuestFieldFocus(pendingFieldId, () => setPendingFieldId(null));

  const recordAttempt = (): void => {
    setAttemptToken((value) => value + 1);
  };

  return {
    record_attempt: recordAttempt,
  };
};
