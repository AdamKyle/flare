import React, { ReactNode } from 'react';

import ClassFormFieldsProps from '../../types/class-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ClassAttributesFields = ({
  state,
  errors,
  on_change: onChange,
}: ClassFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <NumberField
        id="class-str-mod"
        label="Strength Modifier"
        value={state.str_mod}
        on_change={(value) => onChange('str_mod', value)}
        error={errors.str_mod}
      />
      <NumberField
        id="class-dur-mod"
        label="Durability Modifier"
        value={state.dur_mod}
        on_change={(value) => onChange('dur_mod', value)}
        error={errors.dur_mod}
      />
      <NumberField
        id="class-dex-mod"
        label="Dexterity Modifier"
        value={state.dex_mod}
        on_change={(value) => onChange('dex_mod', value)}
        error={errors.dex_mod}
      />
      <NumberField
        id="class-chr-mod"
        label="Charisma Modifier"
        value={state.chr_mod}
        on_change={(value) => onChange('chr_mod', value)}
        error={errors.chr_mod}
      />
      <NumberField
        id="class-int-mod"
        label="Intelligence Modifier"
        value={state.int_mod}
        on_change={(value) => onChange('int_mod', value)}
        error={errors.int_mod}
      />
      <NumberField
        id="class-agi-mod"
        label="Agility Modifier"
        value={state.agi_mod}
        on_change={(value) => onChange('agi_mod', value)}
        error={errors.agi_mod}
      />
      <NumberField
        id="class-focus-mod"
        label="Focus Modifier"
        value={state.focus_mod}
        on_change={(value) => onChange('focus_mod', value)}
        error={errors.focus_mod}
      />
    </div>
  );
};

export default ClassAttributesFields;
