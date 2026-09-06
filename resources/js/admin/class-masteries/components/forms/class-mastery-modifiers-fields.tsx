import React, { ReactNode } from 'react';

import ClassMasteryFormFieldsProps from '../../types/class-mastery-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ClassMasteryModifiersFields = ({
  state,
  errors,
  on_change: onChange,
}: ClassMasteryFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="class-mastery-base-damage-mod"
        label="Base Damage Modifier"
        value={state.base_damage_mod}
        on_change={(value) => onChange('base_damage_mod', value)}
        error={errors.base_damage_mod}
      />
      <NumberField
        id="class-mastery-base-ac-mod"
        label="Base AC Modifier"
        value={state.base_ac_mod}
        on_change={(value) => onChange('base_ac_mod', value)}
        error={errors.base_ac_mod}
      />
      <NumberField
        id="class-mastery-base-healing-mod"
        label="Base Healing Modifier"
        value={state.base_healing_mod}
        on_change={(value) => onChange('base_healing_mod', value)}
        error={errors.base_healing_mod}
      />
      <NumberField
        id="class-mastery-base-spell-damage-mod"
        label="Base Spell Damage Modifier"
        value={state.base_spell_damage_mod}
        on_change={(value) => onChange('base_spell_damage_mod', value)}
        error={errors.base_spell_damage_mod}
      />
      <NumberField
        id="class-mastery-health-mod"
        label="Health Modifier"
        value={state.health_mod}
        on_change={(value) => onChange('health_mod', value)}
        error={errors.health_mod}
      />
      <NumberField
        id="class-mastery-base-damage-stat-increase"
        label="Base Damage Stat Increase"
        value={state.base_damage_stat_increase}
        on_change={(value) => onChange('base_damage_stat_increase', value)}
        error={errors.base_damage_stat_increase}
      />
    </div>
  );
};

export default ClassMasteryModifiersFields;
