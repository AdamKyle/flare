import React, { ReactNode } from 'react';

import MonsterFormFieldsProps from '../../types/monster-form-fields-props';

import NumberField from 'ui/forms/number-field';

const MonsterCombatFields = ({
  state,
  errors,
  on_change: onChange,
}: MonsterFormFieldsProps): ReactNode => (
  <div className="space-y-4">
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <NumberField
        id="monster-str"
        label="Strength"
        value={state.str}
        on_change={(v) => onChange('str', v)}
        min={0}
        error={errors.str}
      />
      <NumberField
        id="monster-dur"
        label="Durability"
        value={state.dur}
        on_change={(v) => onChange('dur', v)}
        min={0}
        error={errors.dur}
      />
      <NumberField
        id="monster-dex"
        label="Dexterity"
        value={state.dex}
        on_change={(v) => onChange('dex', v)}
        min={0}
        error={errors.dex}
      />
      <NumberField
        id="monster-chr"
        label="Charisma"
        value={state.chr}
        on_change={(v) => onChange('chr', v)}
        min={0}
        error={errors.chr}
      />
      <NumberField
        id="monster-int"
        label="Intelligence"
        value={state.int}
        on_change={(v) => onChange('int', v)}
        min={0}
        error={errors.int}
      />
      <NumberField
        id="monster-agi"
        label="Agility"
        value={state.agi}
        on_change={(v) => onChange('agi', v)}
        min={0}
        error={errors.agi}
      />
      <NumberField
        id="monster-focus"
        label="Focus"
        value={state.focus}
        on_change={(v) => onChange('focus', v)}
        min={0}
        error={errors.focus}
      />
      <NumberField
        id="monster-ac"
        label="Armor Class"
        value={state.ac}
        on_change={(v) => onChange('ac', v)}
        min={0}
        error={errors.ac}
      />
    </div>
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <NumberField
        id="monster-accuracy"
        label="Accuracy"
        value={state.accuracy}
        on_change={(v) => onChange('accuracy', v)}
        min={0}
        error={errors.accuracy}
      />
      <NumberField
        id="monster-dodge"
        label="Dodge"
        value={state.dodge}
        on_change={(v) => onChange('dodge', v)}
        min={0}
        error={errors.dodge}
      />
      <NumberField
        id="monster-criticality"
        label="Criticality"
        value={state.criticality}
        on_change={(v) => onChange('criticality', v)}
        min={0}
        error={errors.criticality}
      />
      <NumberField
        id="monster-ambush-chance"
        label="Ambush Chance"
        value={state.ambush_chance}
        on_change={(v) => onChange('ambush_chance', v)}
        min={0}
        error={errors.ambush_chance}
      />
      <NumberField
        id="monster-ambush-resistance"
        label="Ambush Resistance"
        value={state.ambush_resistance}
        on_change={(v) => onChange('ambush_resistance', v)}
        min={0}
        error={errors.ambush_resistance}
      />
      <NumberField
        id="monster-counter-chance"
        label="Counter Chance"
        value={state.counter_chance}
        on_change={(v) => onChange('counter_chance', v)}
        min={0}
        error={errors.counter_chance}
      />
      <NumberField
        id="monster-counter-resistance"
        label="Counter Resistance"
        value={state.counter_resistance}
        on_change={(v) => onChange('counter_resistance', v)}
        min={0}
        error={errors.counter_resistance}
      />
    </div>
  </div>
);

export default MonsterCombatFields;
