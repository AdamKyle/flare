import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../utils/focus-and-scroll-to-field';
import SkillFormErrorsDefinition from '../definitions/skill-form-errors-definition';

const STEP_FIELDS = [
  [
    { field: 'name', id: 'skill-name' },
    { field: 'description', id: 'skill-description' },
    { field: 'max_level', id: 'skill-max-level' },
    { field: 'can_train', id: 'skill-can-train' },
    { field: 'is_locked', id: 'skill-is-locked' },
    { field: 'type', id: 'skill-type' },
  ],
  [
    {
      field: 'base_damage_mod_bonus_per_level',
      id: 'skill-base-damage-mod',
    },
    {
      field: 'base_healing_mod_bonus_per_level',
      id: 'skill-base-healing-mod',
    },
    { field: 'base_ac_mod_bonus_per_level', id: 'skill-base-ac-mod' },
    { field: 'skill_bonus_per_level', id: 'skill-bonus-per-level' },
  ],
  [
    { field: 'class_bonus', id: 'skill-class-bonus' },
    {
      field: 'fight_time_out_mod_bonus_per_level',
      id: 'skill-fight-timeout-mod',
    },
    {
      field: 'move_time_out_mod_bonus_per_level',
      id: 'skill-move-timeout-mod',
    },
    { field: 'game_class_id', id: 'skill-game-class' },
  ],
  [
    { field: 'unit_time_reduction', id: 'skill-unit-time-reduction' },
    { field: 'building_time_reduction', id: 'skill-building-time-reduction' },
    {
      field: 'unit_movement_time_reduction',
      id: 'skill-unit-movement-time-reduction',
    },
  ],
] as const;

export const useFocusFirstInvalidSkillField = (
  errors: SkillFormErrorsDefinition,
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
