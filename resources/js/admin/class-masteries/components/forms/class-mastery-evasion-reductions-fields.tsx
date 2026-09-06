import React, { ReactNode } from 'react';

import ClassMasteryFormFieldsProps from '../../types/class-mastery-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ClassMasteryEvasionReductionsFields = ({
  state,
  errors,
  on_change: onChange,
}: ClassMasteryFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="class-mastery-spell-evasion"
        label="Spell Evasion"
        value={state.spell_evasion}
        on_change={(value) => onChange('spell_evasion', value)}
        error={errors.spell_evasion}
      />
      <NumberField
        id="class-mastery-affix-damage-reduction"
        label="Affix Damage Reduction"
        value={state.affix_damage_reduction}
        on_change={(value) => onChange('affix_damage_reduction', value)}
        error={errors.affix_damage_reduction}
      />
      <NumberField
        id="class-mastery-healing-reduction"
        label="Healing Reduction"
        value={state.healing_reduction}
        on_change={(value) => onChange('healing_reduction', value)}
        error={errors.healing_reduction}
      />
      <NumberField
        id="class-mastery-skill-reduction"
        label="Skill Reduction"
        value={state.skill_reduction}
        on_change={(value) => onChange('skill_reduction', value)}
        error={errors.skill_reduction}
      />
      <NumberField
        id="class-mastery-resistance-reduction"
        label="Resistance Reduction"
        value={state.resistance_reduction}
        on_change={(value) => onChange('resistance_reduction', value)}
        error={errors.resistance_reduction}
      />
    </div>
  );
};

export default ClassMasteryEvasionReductionsFields;
