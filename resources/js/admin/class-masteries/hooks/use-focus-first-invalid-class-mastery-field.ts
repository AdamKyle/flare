import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../utils/focus-and-scroll-to-field';
import ClassMasteryFormErrorsDefinition from '../definitions/class-mastery-form-errors-definition';

const STEP_FIELDS = [
  [
    { field: 'game_class_id', id: 'class-mastery-game-class' },
    { field: 'name', id: 'class-mastery-name' },
    { field: 'description', id: 'class-mastery-description' },
    {
      field: 'requires_class_rank_level',
      id: 'class-mastery-requires-class-rank-level',
    },
  ],
  [
    { field: 'specialty_damage', id: 'class-mastery-specialty-damage' },
    {
      field: 'increase_specialty_damage_per_level',
      id: 'class-mastery-increase-specialty-damage-per-level',
    },
    {
      field: 'specialty_damage_uses_damage_stat_amount',
      id: 'class-mastery-specialty-damage-uses-damage-stat-amount',
    },
    { field: 'attack_type_required', id: 'class-mastery-attack-type-required' },
  ],
  [
    { field: 'base_damage_mod', id: 'class-mastery-base-damage-mod' },
    { field: 'base_ac_mod', id: 'class-mastery-base-ac-mod' },
    { field: 'base_healing_mod', id: 'class-mastery-base-healing-mod' },
    {
      field: 'base_spell_damage_mod',
      id: 'class-mastery-base-spell-damage-mod',
    },
    { field: 'health_mod', id: 'class-mastery-health-mod' },
    {
      field: 'base_damage_stat_increase',
      id: 'class-mastery-base-damage-stat-increase',
    },
  ],
  [
    { field: 'spell_evasion', id: 'class-mastery-spell-evasion' },
    {
      field: 'affix_damage_reduction',
      id: 'class-mastery-affix-damage-reduction',
    },
    { field: 'healing_reduction', id: 'class-mastery-healing-reduction' },
    { field: 'skill_reduction', id: 'class-mastery-skill-reduction' },
    { field: 'resistance_reduction', id: 'class-mastery-resistance-reduction' },
  ],
] as const;

export const useFocusFirstInvalidClassMasteryField = (
  errors: ClassMasteryFormErrorsDefinition,
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
