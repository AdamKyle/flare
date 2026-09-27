import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../../utils/focus-and-scroll-to-field';
import UnitFormErrorsDefinition from '../definitions/unit-form-errors-definition';

const STEP_FIELDS = [
  [
    { field: 'name', id: 'unit-name' },
    { field: 'description', id: 'unit-description' },
    { field: 'attack', id: 'unit-attack' },
    { field: 'defence', id: 'unit-defence' },
    { field: 'can_heal', id: 'unit-can-heal' },
    { field: 'heal_percentage', id: 'unit-heal-percentage' },
    { field: 'is_settler', id: 'unit-is-settler' },
    { field: 'reduces_morale_by', id: 'unit-reduces-morale-by' },
    { field: 'attacker', id: 'unit-attacker' },
    { field: 'defender', id: 'unit-defender' },
    { field: 'siege_weapon', id: 'unit-siege-weapon' },
    { field: 'is_airship', id: 'unit-is-airship' },
    { field: 'is_special', id: 'unit-is-special' },
    { field: 'can_not_be_healed', id: 'unit-can-not-be-healed' },
    { field: 'time_to_recruit', id: 'unit-time-to-recruit' },
  ],
  [
    { field: 'wood_cost', id: 'unit-wood-cost' },
    { field: 'stone_cost', id: 'unit-stone-cost' },
    { field: 'clay_cost', id: 'unit-clay-cost' },
    { field: 'iron_cost', id: 'unit-iron-cost' },
    { field: 'steel_cost', id: 'unit-steel-cost' },
    { field: 'required_population', id: 'unit-required-population' },
  ],
] as const;

export const useFocusFirstInvalidUnitField = (
  errors: UnitFormErrorsDefinition,
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
