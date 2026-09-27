import { useEffect, useState } from 'react';

import { focusAndScrollToField } from '../../../../utils/focus-and-scroll-to-field';
import BuildingFormErrorsDefinition from '../definitions/building-form-errors-definition';

const STEP_FIELDS = [
  [
    { field: 'name', id: 'building-name' },
    { field: 'description', id: 'building-description' },
    { field: 'max_level', id: 'building-max-level' },
    { field: 'required_population', id: 'building-required-population' },
    { field: 'base_durability', id: 'building-base-durability' },
    { field: 'base_defence', id: 'building-base-defence' },
    { field: 'passive_skill_id', id: 'building-passive-skill' },
    { field: 'level_required', id: 'building-level-required' },
  ],
  [
    { field: 'wood_cost', id: 'building-wood-cost' },
    { field: 'clay_cost', id: 'building-clay-cost' },
    { field: 'stone_cost', id: 'building-stone-cost' },
    { field: 'iron_cost', id: 'building-iron-cost' },
    { field: 'steel_cost', id: 'building-steel-cost' },
    { field: 'time_to_build', id: 'building-time-to-build' },
    { field: 'time_increase_amount', id: 'building-time-increase-amount' },
  ],
  [
    {
      field: 'increase_population_amount',
      id: 'building-increase-population',
    },
    { field: 'increase_morale_amount', id: 'building-increase-morale' },
    { field: 'decrease_morale_amount', id: 'building-decrease-morale' },
    { field: 'increase_wood_amount', id: 'building-increase-wood' },
    { field: 'increase_clay_amount', id: 'building-increase-clay' },
    { field: 'increase_stone_amount', id: 'building-increase-stone' },
    { field: 'increase_iron_amount', id: 'building-increase-iron' },
    {
      field: 'increase_durability_amount',
      id: 'building-increase-durability',
    },
    { field: 'increase_defence_amount', id: 'building-increase-defence' },
  ],
  [
    { field: 'trains_units', id: 'building-trains-units' },
    { field: 'unit_ids', id: 'building-units-to-recruit' },
    { field: 'units_per_level', id: 'building-units-per-level' },
    { field: 'only_at_level', id: 'building-only-at-level' },
  ],
] as const;

export const useFocusFirstInvalidBuildingField = (
  errors: BuildingFormErrorsDefinition,
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
