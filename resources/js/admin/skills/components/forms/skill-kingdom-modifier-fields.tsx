import React, { ReactNode } from 'react';

import SkillFormFieldsProps from '../../types/skill-form-fields-props';

import NumberField from 'ui/forms/number-field';

const SkillKingdomModifierFields = ({
  state,
  errors,
  on_change: onChange,
}: SkillFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="skill-unit-time-reduction"
        label="Unit Recruitment Time Reduction %"
        value={state.unit_time_reduction}
        on_change={(value) => onChange('unit_time_reduction', value)}
        error={errors.unit_time_reduction}
        description="A decimal fraction, e.g. 0.01 for 1%."
      />
      <NumberField
        id="skill-building-time-reduction"
        label="Building Time Reduction %"
        value={state.building_time_reduction}
        on_change={(value) => onChange('building_time_reduction', value)}
        error={errors.building_time_reduction}
        description="A decimal fraction, e.g. 0.01 for 1%."
      />
      <NumberField
        id="skill-unit-movement-time-reduction"
        label="Unit Movement Time Reduction %"
        value={state.unit_movement_time_reduction}
        on_change={(value) => onChange('unit_movement_time_reduction', value)}
        error={errors.unit_movement_time_reduction}
        description="A decimal fraction, e.g. 0.01 for 1%."
      />
    </div>
  );
};

export default SkillKingdomModifierFields;
