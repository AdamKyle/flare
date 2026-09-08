import { useEffect, useRef, useState } from 'react';

import UseFocusFirstInvalidMonsterFieldDefinition from './definitions/use-focus-first-invalid-monster-field-definition';
import { useMonsterFieldFocus } from './use-monster-field-focus';
import MonsterFormErrorsDefinition from '../definitions/monster-form-errors-definition';
import { resolveFirstInvalidMonsterField } from '../utils/resolve-first-invalid-monster-field';

export const useFocusFirstInvalidMonsterField = (
  fieldErrors: MonsterFormErrorsDefinition,
  currentStepIndex: number
): UseFocusFirstInvalidMonsterFieldDefinition => {
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
      resolveFirstInvalidMonsterField(fieldErrors, currentStepIndex)
    );
  }, [attemptToken, fieldErrors, currentStepIndex]);

  useMonsterFieldFocus(pendingFieldId, () => setPendingFieldId(null));

  const recordAttempt = (): void => {
    setAttemptToken((value) => value + 1);
  };

  return {
    record_attempt: recordAttempt,
  };
};
