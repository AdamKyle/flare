import React, { ReactNode } from 'react';

import SkillFormFieldsProps from '../../types/skill-form-fields-props';

import NumberField from 'ui/forms/number-field';

const SkillCharacterModifierFields = ({
  state,
  errors,
  on_change: onChange,
}: SkillFormFieldsProps): ReactNode => {
  return (
    <div className="grid gap-4 md:grid-cols-2">
      <NumberField
        id="skill-base-damage-mod"
        label="Base Damage Modifier % per level"
        value={state.base_damage_mod_bonus_per_level}
        on_change={(value) =>
          onChange('base_damage_mod_bonus_per_level', value)
        }
        error={errors.base_damage_mod_bonus_per_level}
        description="A decimal fraction, e.g. 0.01 for 1%."
      />
      <NumberField
        id="skill-base-healing-mod"
        label="Base Healing Modifier % per level"
        value={state.base_healing_mod_bonus_per_level}
        on_change={(value) =>
          onChange('base_healing_mod_bonus_per_level', value)
        }
        error={errors.base_healing_mod_bonus_per_level}
        description="A decimal fraction, e.g. 0.01 for 1%."
      />
      <NumberField
        id="skill-base-ac-mod"
        label="Base AC Modifier % per level"
        value={state.base_ac_mod_bonus_per_level}
        on_change={(value) => onChange('base_ac_mod_bonus_per_level', value)}
        error={errors.base_ac_mod_bonus_per_level}
        description="A decimal fraction, e.g. 0.01 for 1%."
      />
      <NumberField
        id="skill-bonus-per-level"
        label="Skill Bonus Per Level"
        value={state.skill_bonus_per_level}
        on_change={(value) => onChange('skill_bonus_per_level', value)}
        error={errors.skill_bonus_per_level}
        description="A decimal fraction, e.g. 0.01 for 1%."
      />
    </div>
  );
};

export default SkillCharacterModifierFields;
