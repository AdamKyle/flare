import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ManageGuideQuestsRequiredStats = ({
  state,
  errors,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => (
  <div className="space-y-4">
    <NumberField
      id="guide-quest-required-stats"
      label="Required Total"
      value={state.required_stats}
      on_change={(value) => onChange('required_stats', value)}
      error={errors.required_stats}
      description="This is the total of all the stats added together"
    />
    <NumberField
      id="guide-quest-required-str"
      label="Required Strength (Total)"
      value={state.required_str}
      on_change={(value) => onChange('required_str', value)}
      error={errors.required_str}
    />
    <NumberField
      id="guide-quest-required-dex"
      label="Required Dexterity (Total)"
      value={state.required_dex}
      on_change={(value) => onChange('required_dex', value)}
      error={errors.required_dex}
    />
    <NumberField
      id="guide-quest-required-int"
      label="Required Intelligence (Total)"
      value={state.required_int}
      on_change={(value) => onChange('required_int', value)}
      error={errors.required_int}
    />
    <NumberField
      id="guide-quest-required-agi"
      label="Required Agility (Total)"
      value={state.required_agi}
      on_change={(value) => onChange('required_agi', value)}
      error={errors.required_agi}
    />
    <NumberField
      id="guide-quest-required-dur"
      label="Required Durability (Total)"
      value={state.required_dur}
      on_change={(value) => onChange('required_dur', value)}
      error={errors.required_dur}
    />
    <NumberField
      id="guide-quest-required-chr"
      label="Required Charisma (Total)"
      value={state.required_chr}
      on_change={(value) => onChange('required_chr', value)}
      error={errors.required_chr}
    />
    <NumberField
      id="guide-quest-required-focus"
      label="Required Focus (Total)"
      value={state.required_focus}
      on_change={(value) => onChange('required_focus', value)}
      error={errors.required_focus}
    />
  </div>
);

export default ManageGuideQuestsRequiredStats;
