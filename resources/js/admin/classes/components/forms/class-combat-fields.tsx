import React, { ReactNode } from 'react';

import ClassFormFieldsProps from '../../types/class-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ClassCombatFields = ({
  state,
  errors,
  on_change: onChange,
}: ClassFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="class-accuracy-mod"
        label="Accuracy Modifier"
        value={state.accuracy_mod}
        on_change={(value) => onChange('accuracy_mod', value)}
        error={errors.accuracy_mod}
        description="A decimal fraction, e.g. 0.05 for 5%."
      />
      <NumberField
        id="class-dodge-mod"
        label="Dodge Modifier"
        value={state.dodge_mod}
        on_change={(value) => onChange('dodge_mod', value)}
        error={errors.dodge_mod}
        description="A decimal fraction, e.g. 0.05 for 5%."
      />
      <NumberField
        id="class-defense-mod"
        label="Defense Modifier"
        value={state.defense_mod}
        on_change={(value) => onChange('defense_mod', value)}
        error={errors.defense_mod}
        description="A decimal fraction, e.g. 0.05 for 5%."
      />
      <NumberField
        id="class-looting-mod"
        label="Looting Modifier"
        value={state.looting_mod}
        on_change={(value) => onChange('looting_mod', value)}
        error={errors.looting_mod}
        description="A decimal fraction, e.g. 0.05 for 5%."
      />
    </div>
  );
};

export default ClassCombatFields;
