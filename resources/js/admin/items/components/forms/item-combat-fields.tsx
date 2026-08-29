import React, { ReactNode } from 'react';

import ItemFormFieldsProps from '../../types/item-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ItemCombatFields = ({
  state,
  errors,
  on_change: onChange,
}: ItemFormFieldsProps): ReactNode => {
  return (
    <div className="space-y-4">
      <div className="grid gap-4 md:grid-cols-3">
        <NumberField
          id="item-base-damage"
          label="Base Damage"
          value={state.base_damage}
          on_change={(value) => onChange('base_damage', value)}
          error={errors.base_damage}
          min={0}
        />
        <NumberField
          id="item-base-ac"
          label="Base AC"
          value={state.base_ac}
          on_change={(value) => onChange('base_ac', value)}
          error={errors.base_ac}
          min={0}
        />
        <NumberField
          id="item-base-healing"
          label="Base Healing"
          value={state.base_healing}
          on_change={(value) => onChange('base_healing', value)}
          error={errors.base_healing}
          min={0}
        />
        <NumberField
          id="item-base-damage-mod"
          label="Base Damage Modifier"
          value={state.base_damage_mod}
          on_change={(value) => onChange('base_damage_mod', value)}
          error={errors.base_damage_mod}
        />
        <NumberField
          id="item-base-ac-mod"
          label="Base AC Modifier"
          value={state.base_ac_mod}
          on_change={(value) => onChange('base_ac_mod', value)}
          error={errors.base_ac_mod}
        />
        <NumberField
          id="item-base-healing-mod"
          label="Base Healing Modifier"
          value={state.base_healing_mod}
          on_change={(value) => onChange('base_healing_mod', value)}
          error={errors.base_healing_mod}
        />
      </div>

      <div className="grid gap-4 md:grid-cols-4">
        <NumberField
          id="item-str-mod"
          label="Strength Modifier"
          value={state.str_mod}
          on_change={(value) => onChange('str_mod', value)}
          error={errors.str_mod}
        />
        <NumberField
          id="item-dur-mod"
          label="Durability Modifier"
          value={state.dur_mod}
          on_change={(value) => onChange('dur_mod', value)}
          error={errors.dur_mod}
        />
        <NumberField
          id="item-dex-mod"
          label="Dexterity Modifier"
          value={state.dex_mod}
          on_change={(value) => onChange('dex_mod', value)}
          error={errors.dex_mod}
        />
        <NumberField
          id="item-chr-mod"
          label="Charisma Modifier"
          value={state.chr_mod}
          on_change={(value) => onChange('chr_mod', value)}
          error={errors.chr_mod}
        />
        <NumberField
          id="item-int-mod"
          label="Intelligence Modifier"
          value={state.int_mod}
          on_change={(value) => onChange('int_mod', value)}
          error={errors.int_mod}
        />
        <NumberField
          id="item-agi-mod"
          label="Agility Modifier"
          value={state.agi_mod}
          on_change={(value) => onChange('agi_mod', value)}
          error={errors.agi_mod}
        />
        <NumberField
          id="item-focus-mod"
          label="Focus Modifier"
          value={state.focus_mod}
          on_change={(value) => onChange('focus_mod', value)}
          error={errors.focus_mod}
        />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <NumberField
          id="item-ambush-chance"
          label="Ambush Chance"
          value={state.ambush_chance}
          on_change={(value) => onChange('ambush_chance', value)}
          error={errors.ambush_chance}
          min={0}
        />
        <NumberField
          id="item-ambush-resistance"
          label="Ambush Resistance"
          value={state.ambush_resistance}
          on_change={(value) => onChange('ambush_resistance', value)}
          error={errors.ambush_resistance}
          min={0}
        />
        <NumberField
          id="item-counter-chance"
          label="Counter Chance"
          value={state.counter_chance}
          on_change={(value) => onChange('counter_chance', value)}
          error={errors.counter_chance}
          min={0}
        />
        <NumberField
          id="item-counter-resistance"
          label="Counter Resistance"
          value={state.counter_resistance}
          on_change={(value) => onChange('counter_resistance', value)}
          error={errors.counter_resistance}
          min={0}
        />
      </div>
    </div>
  );
};

export default ItemCombatFields;
