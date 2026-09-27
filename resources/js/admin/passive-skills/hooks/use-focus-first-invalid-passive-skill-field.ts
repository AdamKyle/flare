import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../utils/focus-and-scroll-to-field';
import PassiveSkillFormErrorsDefinition from '../definitions/passive-skill-form-errors-definition';

const STEP_FIELDS = [
  [
    { field: 'name', id: 'passive-skill-name' },
    { field: 'description', id: 'passive-skill-description' },
    { field: 'effect_type', id: 'passive-skill-effect' },
    { field: 'max_level', id: 'passive-skill-max-level' },
    { field: 'hours_per_level', id: 'passive-skill-hours-per-level' },
    { field: 'is_locked', id: 'passive-skill-is-locked' },
    { field: 'is_parent', id: 'passive-skill-is-parent' },
  ],
  [
    { field: 'bonus_per_level', id: 'passive-skill-bonus-per-level' },
    {
      field: 'resource_bonus_per_level',
      id: 'passive-skill-resource-bonus-per-level',
    },
    {
      field: 'capital_city_building_request_travel_time_reduction',
      id: 'passive-skill-building-request-reduction',
    },
    {
      field: 'capital_city_unit_request_travel_time_reduction',
      id: 'passive-skill-unit-request-reduction',
    },
    {
      field: 'resource_request_time_reduction',
      id: 'passive-skill-resource-request-reduction',
    },
  ],
  [
    { field: 'parent_skill_id', id: 'passive-skill-parent' },
    { field: 'unlocks_at_level', id: 'passive-skill-unlocks-at-level' },
  ],
] as const;

export const useFocusFirstInvalidPassiveSkillField = (
  errors: PassiveSkillFormErrorsDefinition,
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
