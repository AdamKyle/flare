import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../utils/focus-and-scroll-to-field';
import RaceFormErrorsDefinition from '../definitions/race-form-errors-definition';

const FIELD_IDS: ReadonlyArray<{
  field: keyof RaceFormErrorsDefinition;
  id: string;
}> = [
  { field: 'name', id: 'race-name' },
  { field: 'description', id: 'race-description' },
];

export const useFocusFirstInvalidRaceField = (
  errors: RaceFormErrorsDefinition
): (() => void) => {
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    if (attempt === 0) return;

    const field = FIELD_IDS.find((candidate) => candidate.field in errors);

    if (field) focusAndScrollToField(field.id);
  }, [attempt, errors]);

  return () => setAttempt((value) => value + 1);
};
