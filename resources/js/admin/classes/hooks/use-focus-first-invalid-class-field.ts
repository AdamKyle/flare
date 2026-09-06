import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../utils/focus-and-scroll-to-field';
import ClassFormErrorsDefinition from '../definitions/class-form-errors-definition';

const STEP_FIELDS = [
  [
    { field: 'name', id: 'class-name' },
    { field: 'description', id: 'class-description' },
    { field: 'damage_stat', id: 'class-damage-stat' },
    { field: 'to_hit_stat', id: 'class-to-hit-stat' },
  ],
  [
    { field: 'str_mod', id: 'class-str-mod' },
    { field: 'dur_mod', id: 'class-dur-mod' },
    { field: 'dex_mod', id: 'class-dex-mod' },
    { field: 'chr_mod', id: 'class-chr-mod' },
    { field: 'int_mod', id: 'class-int-mod' },
    { field: 'agi_mod', id: 'class-agi-mod' },
    { field: 'focus_mod', id: 'class-focus-mod' },
  ],
  [
    { field: 'accuracy_mod', id: 'class-accuracy-mod' },
    { field: 'dodge_mod', id: 'class-dodge-mod' },
    { field: 'defense_mod', id: 'class-defense-mod' },
    { field: 'looting_mod', id: 'class-looting-mod' },
  ],
  [
    { field: 'primary_required_class_id', id: 'class-primary-required-class' },
    {
      field: 'primary_required_class_level',
      id: 'class-primary-required-level',
    },
    {
      field: 'secondary_required_class_id',
      id: 'class-secondary-required-class',
    },
    {
      field: 'secondary_required_class_level',
      id: 'class-secondary-required-level',
    },
  ],
] as const;

export const useFocusFirstInvalidClassField = (
  errors: ClassFormErrorsDefinition,
  stepIndex: number,
  goToStep: (stepIndex: number) => void
): (() => void) => {
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    if (attempt === 0) return;

    const stepIndexWithError = STEP_FIELDS.findIndex((fields) =>
      fields.some((candidate) => candidate.field in errors)
    );

    if (stepIndexWithError === -1) return;

    if (stepIndexWithError !== stepIndex) {
      goToStep(stepIndexWithError);

      return;
    }

    const field = STEP_FIELDS[stepIndexWithError]?.find(
      (candidate) => candidate.field in errors
    );
    if (field) focusAndScrollToField(field.id);
  }, [attempt, errors, stepIndex, goToStep]);

  return () => setAttempt((value) => value + 1);
};
