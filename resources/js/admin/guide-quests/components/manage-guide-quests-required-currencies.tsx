import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ManageGuideQuestsRequiredCurrencies = ({
  state,
  errors,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => (
  <div className="space-y-4">
    <NumberField
      id="guide-quest-required-gold"
      label="Required Gold"
      value={state.required_gold}
      on_change={(value) => onChange('required_gold', value)}
      error={errors.required_gold}
    />
    <NumberField
      id="guide-quest-required-gold-dust"
      label="Required Gold Dust"
      value={state.required_gold_dust}
      on_change={(value) => onChange('required_gold_dust', value)}
      error={errors.required_gold_dust}
    />
    <NumberField
      id="guide-quest-required-shards"
      label="Required Shards"
      value={state.required_shards}
      on_change={(value) => onChange('required_shards', value)}
      error={errors.required_shards}
    />
    <NumberField
      id="guide-quest-required-copper-coins"
      label="Required Copper Coins"
      value={state.required_copper_coins}
      on_change={(value) => onChange('required_copper_coins', value)}
      error={errors.required_copper_coins}
    />
    <NumberField
      id="guide-quest-required-gold-bars"
      label="Required Gold Bars"
      value={state.required_gold_bars}
      on_change={(value) => onChange('required_gold_bars', value)}
      error={errors.required_gold_bars}
    />
  </div>
);

export default ManageGuideQuestsRequiredCurrencies;
