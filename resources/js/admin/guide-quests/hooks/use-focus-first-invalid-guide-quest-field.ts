import { useCallback, useEffect, useRef, useState } from 'react';

import UseFocusFirstInvalidGuideQuestFieldDefinition from './definitions/use-focus-first-invalid-guide-quest-field-definition';
import GuideQuestFormErrorsDefinition from '../definitions/guide-quest-form-errors-definition';

export const useFocusFirstInvalidGuideQuestField = (
  fieldErrors: GuideQuestFormErrorsDefinition,
  currentStepIndex: number
): UseFocusFirstInvalidGuideQuestFieldDefinition => {
  const [attemptToken, setAttemptToken] = useState(0);
  const consumedAttemptTokenRef = useRef(0);

  useEffect(() => {
    if (
      attemptToken === 0 ||
      attemptToken === consumedAttemptTokenRef.current ||
      Object.keys(fieldErrors).length === 0
    ) {
      return;
    }

    consumedAttemptTokenRef.current = attemptToken;
    window.requestAnimationFrame(() => {
      const invalidField = document.querySelector<HTMLElement>(
        '[aria-invalid="true"]'
      );
      invalidField?.focus();
      invalidField?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  }, [attemptToken, currentStepIndex, fieldErrors]);

  const recordAttempt = useCallback(
    () => setAttemptToken((value) => value + 1),
    []
  );

  return { record_attempt: recordAttempt };
};
