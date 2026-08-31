import React, { ReactNode } from 'react';

import MonsterFormFieldsProps from '../../types/monster-form-fields-props';

import CheckboxField from 'ui/forms/checkbox-field';
import NumberField from 'ui/forms/number-field';

const MonsterSpellFields = ({
  state,
  errors,
  on_change: onChange,
}: MonsterFormFieldsProps): ReactNode => (
  <div className="space-y-4">
    <CheckboxField
      id="monster-can-cast"
      label="Can Cast"
      checked={state.can_cast}
      on_change={(value) => onChange('can_cast', value)}
    />
    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <NumberField
        id="monster-max-spell-damage"
        label="Max Spell Damage"
        value={state.max_spell_damage}
        on_change={(v) => onChange('max_spell_damage', v)}
        min={0}
        disabled={!state.can_cast}
        error={errors.max_spell_damage}
      />
      <NumberField
        id="monster-casting-accuracy"
        label="Casting Accuracy"
        value={state.casting_accuracy}
        on_change={(v) => onChange('casting_accuracy', v)}
        min={0}
        error={errors.casting_accuracy}
      />
      <NumberField
        id="monster-spell-evasion"
        label="Spell Evasion"
        value={state.spell_evasion}
        on_change={(v) => onChange('spell_evasion', v)}
        min={0}
        error={errors.spell_evasion}
      />
      <NumberField
        id="monster-max-affix-damage"
        label="Max Affix Damage"
        value={state.max_affix_damage}
        on_change={(v) => onChange('max_affix_damage', v)}
        min={0}
        error={errors.max_affix_damage}
      />
      <NumberField
        id="monster-affix-resistance"
        label="Affix Resistance"
        value={state.affix_resistance}
        on_change={(v) => onChange('affix_resistance', v)}
        min={0}
        error={errors.affix_resistance}
      />
      <NumberField
        id="monster-healing-percentage"
        label="Healing Percentage"
        value={state.healing_percentage}
        on_change={(v) => onChange('healing_percentage', v)}
        min={0}
        error={errors.healing_percentage}
      />
      <NumberField
        id="monster-entrancing-chance"
        label="Entrancing Chance"
        value={state.entrancing_chance}
        on_change={(v) => onChange('entrancing_chance', v)}
        min={0}
        error={errors.entrancing_chance}
      />
      <NumberField
        id="monster-devouring-light-chance"
        label="Devouring Light Chance"
        value={state.devouring_light_chance}
        on_change={(v) => onChange('devouring_light_chance', v)}
        min={0}
        error={errors.devouring_light_chance}
      />
      <NumberField
        id="monster-devouring-darkness-chance"
        label="Devouring Darkness Chance"
        value={state.devouring_darkness_chance}
        on_change={(v) => onChange('devouring_darkness_chance', v)}
        min={0}
        error={errors.devouring_darkness_chance}
      />
      <NumberField
        id="monster-life-stealing-resistance"
        label="Life Stealing Resistance"
        value={state.life_stealing_resistance}
        on_change={(v) => onChange('life_stealing_resistance', v)}
        min={0}
        error={errors.life_stealing_resistance}
      />
    </div>
  </div>
);

export default MonsterSpellFields;
