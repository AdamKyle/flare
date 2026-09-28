import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../utils/focus-and-scroll-to-field';
import GemAbilityFormErrorsDefinition from '../definitions/gem-ability-form-errors-definition';

const STEP_FIELDS = [
  [
    { field: 'name', id: 'gem-ability-name' },
    { field: 'description', id: 'gem-ability-description' },
    { field: 'ability_type', id: 'gem-ability-ability-type' },
    { field: 'enabled', id: 'gem-ability-enabled' },
  ],
  [
    { field: 'effect_type', id: 'gem-ability-effect-type' },
    { field: 'attack_types', id: 'gem-ability-attack-type-attack' },
    { field: 'proc_chance', id: 'gem-ability-proc-chance' },
    { field: 'effect_value', id: 'gem-ability-effect-value' },
    { field: 'scaling_source', id: 'gem-ability-scaling-source' },
  ],
] as const;

export const useFocusFirstInvalidGemAbilityField = (
  errors: GemAbilityFormErrorsDefinition,
  stepIndex: number,
  goToStep: (stepIndex: number) => void
): (() => void) => {
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    if (attempt === 0) {
      return;
    }

    const stepIndexWithError = STEP_FIELDS.findIndex((fields) =>
      fields.some((candidate) => candidate.field in errors)
    );

    if (stepIndexWithError === -1) {
      return;
    }

    if (stepIndexWithError !== stepIndex) {
      goToStep(stepIndexWithError);

      return;
    }

    const field = STEP_FIELDS[stepIndexWithError]?.find(
      (candidate) => candidate.field in errors
    );

    if (!field) {
      return;
    }

    focusAndScrollToField(field.id);
  }, [attempt, errors, stepIndex, goToStep]);

  return () => setAttempt((value) => value + 1);
};
