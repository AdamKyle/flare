import React, { ReactNode } from 'react';

import PassiveSkillFormFieldsProps from '../../types/passive-skill-form-fields-props';

import NumberField from 'ui/forms/number-field';

const PassiveSkillBonusFields = ({
  state,
  errors,
  on_change: onChange,
}: PassiveSkillFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="passive-skill-bonus-per-level"
        label="Bonus Per Level %"
        value={state.bonus_per_level}
        on_change={(value) => onChange('bonus_per_level', value)}
        error={errors.bonus_per_level}
        description="A decimal fraction, e.g. 0.05 for 5%."
      />
      <NumberField
        id="passive-skill-resource-bonus-per-level"
        label="Bonus Resources Per Level"
        value={state.resource_bonus_per_level}
        on_change={(value) => onChange('resource_bonus_per_level', value)}
        error={errors.resource_bonus_per_level}
      />
      <NumberField
        id="passive-skill-building-request-reduction"
        label="Capital City Building Request Travel Time Reduction %"
        value={state.capital_city_building_request_travel_time_reduction}
        on_change={(value) =>
          onChange('capital_city_building_request_travel_time_reduction', value)
        }
        error={errors.capital_city_building_request_travel_time_reduction}
        description="A decimal fraction, e.g. 0.05 for 5%."
      />
      <NumberField
        id="passive-skill-unit-request-reduction"
        label="Capital City Unit Request Travel Time Reduction %"
        value={state.capital_city_unit_request_travel_time_reduction}
        on_change={(value) =>
          onChange('capital_city_unit_request_travel_time_reduction', value)
        }
        error={errors.capital_city_unit_request_travel_time_reduction}
        description="A decimal fraction, e.g. 0.05 for 5%."
      />
      <NumberField
        id="passive-skill-resource-request-reduction"
        label="Resource Request Travel Time Reduction %"
        value={state.resource_request_time_reduction}
        on_change={(value) =>
          onChange('resource_request_time_reduction', value)
        }
        error={errors.resource_request_time_reduction}
        description="A decimal fraction, e.g. 0.05 for 5%."
      />
    </div>
  );
};

export default PassiveSkillBonusFields;
