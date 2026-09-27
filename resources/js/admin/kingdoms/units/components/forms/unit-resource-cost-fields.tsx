import React, { ReactNode } from 'react';

import UnitFormFieldsProps from '../../types/unit-form-fields-props';

import NumberField from 'ui/forms/number-field';

const UnitResourceCostFields = ({
  state,
  errors,
  on_change: onChange,
}: UnitFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="unit-wood-cost"
        label="Wood"
        value={state.wood_cost}
        on_change={(value) => onChange('wood_cost', value)}
        error={errors.wood_cost}
      />
      <NumberField
        id="unit-stone-cost"
        label="Stone"
        value={state.stone_cost}
        on_change={(value) => onChange('stone_cost', value)}
        error={errors.stone_cost}
      />
      <NumberField
        id="unit-clay-cost"
        label="Clay"
        value={state.clay_cost}
        on_change={(value) => onChange('clay_cost', value)}
        error={errors.clay_cost}
      />
      <NumberField
        id="unit-iron-cost"
        label="Iron"
        value={state.iron_cost}
        on_change={(value) => onChange('iron_cost', value)}
        error={errors.iron_cost}
      />
      <NumberField
        id="unit-steel-cost"
        label="Steel"
        value={state.steel_cost}
        on_change={(value) => onChange('steel_cost', value)}
        error={errors.steel_cost}
      />
      <NumberField
        id="unit-required-population"
        label="Population"
        value={state.required_population}
        on_change={(value) => onChange('required_population', value)}
        error={errors.required_population}
      />
    </div>
  );
};

export default UnitResourceCostFields;
