import { useEffect, useRef, useState } from 'react';

import UseInvalidLocationFieldTargetDefinition from './definitions/use-invalid-location-field-target-definition';
import { LocationFormStep } from '../enums/location-form-step';
import LocationFormErrors from '../types/location-form-errors';
import { resolveFirstInvalidLocationField } from '../utils/resolve-first-invalid-location-field';

export const useInvalidLocationFieldTarget = (
  fieldErrors: LocationFormErrors,
  setCurrentStepIndex: (index: number) => void
): UseInvalidLocationFieldTargetDefinition => {
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

    const target = resolveFirstInvalidLocationField(fieldErrors);

    if (!target) {
      return;
    }

    setCurrentStepIndex(Object.values(LocationFormStep).indexOf(target.step));
    setPendingFieldId(target.id);
  }, [attemptToken, fieldErrors, setCurrentStepIndex]);

  const recordAttempt = (): void => {
    setAttemptToken((value) => value + 1);
  };

  const clearPendingFieldId = (): void => {
    setPendingFieldId(null);
  };

  return {
    pending_field_id: pendingFieldId,
    clear_pending_field_id: clearPendingFieldId,
    record_attempt: recordAttempt,
  };
};
