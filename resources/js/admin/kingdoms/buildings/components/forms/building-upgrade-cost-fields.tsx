import React, { ReactNode } from 'react';

import BuildingFormFieldsProps from '../../types/building-form-fields-props';

import NumberField from 'ui/forms/number-field';

const BuildingUpgradeCostFields = ({
  state,
  errors,
  on_change: onChange,
}: BuildingFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="building-wood-cost"
        label="Wood"
        value={state.wood_cost}
        on_change={(value) => onChange('wood_cost', value)}
        error={errors.wood_cost}
        required
      />
      <NumberField
        id="building-clay-cost"
        label="Clay"
        value={state.clay_cost}
        on_change={(value) => onChange('clay_cost', value)}
        error={errors.clay_cost}
        required
      />
      <NumberField
        id="building-stone-cost"
        label="Stone"
        value={state.stone_cost}
        on_change={(value) => onChange('stone_cost', value)}
        error={errors.stone_cost}
        required
      />
      <NumberField
        id="building-iron-cost"
        label="Iron"
        value={state.iron_cost}
        on_change={(value) => onChange('iron_cost', value)}
        error={errors.iron_cost}
        required
      />
      <NumberField
        id="building-steel-cost"
        label="Steel"
        value={state.steel_cost}
        on_change={(value) => onChange('steel_cost', value)}
        error={errors.steel_cost}
      />
      <NumberField
        id="building-time-to-build"
        label="Time to Build (minutes)"
        value={state.time_to_build}
        on_change={(value) => onChange('time_to_build', value)}
        error={errors.time_to_build}
        required
      />
      <NumberField
        id="building-time-increase-amount"
        label="Time Increase Per Level %"
        value={state.time_increase_amount}
        on_change={(value) => onChange('time_increase_amount', value)}
        error={errors.time_increase_amount}
        description="A decimal fraction, e.g. 0.05 for 5%."
        required
      />
    </div>
  );
};

export default BuildingUpgradeCostFields;
