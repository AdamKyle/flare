import React, { ReactNode } from 'react';

import BuildingFormFieldsProps from '../../types/building-form-fields-props';

import NumberField from 'ui/forms/number-field';

const BuildingUpgradeEffectFields = ({
  state,
  errors,
  on_change: onChange,
}: BuildingFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="building-increase-population"
        label="Increase Population Amount"
        value={state.increase_population_amount}
        on_change={(value) => onChange('increase_population_amount', value)}
        error={errors.increase_population_amount}
        required
      />
      <NumberField
        id="building-increase-morale"
        label="Increase Morale %"
        value={state.increase_morale_amount}
        on_change={(value) => onChange('increase_morale_amount', value)}
        error={errors.increase_morale_amount}
        description="A decimal fraction, e.g. 0.05 for 5%."
        required
      />
      <NumberField
        id="building-decrease-morale"
        label="Decrease Morale %"
        value={state.decrease_morale_amount}
        on_change={(value) => onChange('decrease_morale_amount', value)}
        error={errors.decrease_morale_amount}
        description="A decimal fraction, e.g. 0.05 for 5%."
        required
      />
      <NumberField
        id="building-increase-wood"
        label="Increase Wood"
        value={state.increase_wood_amount}
        on_change={(value) => onChange('increase_wood_amount', value)}
        error={errors.increase_wood_amount}
        required
      />
      <NumberField
        id="building-increase-clay"
        label="Increase Clay"
        value={state.increase_clay_amount}
        on_change={(value) => onChange('increase_clay_amount', value)}
        error={errors.increase_clay_amount}
        required
      />
      <NumberField
        id="building-increase-stone"
        label="Increase Stone"
        value={state.increase_stone_amount}
        on_change={(value) => onChange('increase_stone_amount', value)}
        error={errors.increase_stone_amount}
        required
      />
      <NumberField
        id="building-increase-iron"
        label="Increase Iron"
        value={state.increase_iron_amount}
        on_change={(value) => onChange('increase_iron_amount', value)}
        error={errors.increase_iron_amount}
        required
      />
      <NumberField
        id="building-increase-durability"
        label="Increase Durability"
        value={state.increase_durability_amount}
        on_change={(value) => onChange('increase_durability_amount', value)}
        error={errors.increase_durability_amount}
        required
      />
      <NumberField
        id="building-increase-defence"
        label="Increase Defence"
        value={state.increase_defence_amount}
        on_change={(value) => onChange('increase_defence_amount', value)}
        error={errors.increase_defence_amount}
        required
      />
    </div>
  );
};

export default BuildingUpgradeEffectFields;
