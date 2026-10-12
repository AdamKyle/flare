import React, { ReactNode } from 'react';

import GuideQuestFormFieldsProps from './types/guide-quest-form-fields-props';

import NumberField from 'ui/forms/number-field';

const ManageGuideQuestsRewardsAndBonuses = ({
  state,
  errors,
  on_change: onChange,
}: GuideQuestFormFieldsProps): ReactNode => (
  <div className="space-y-4">
    <NumberField
      id="guide-quest-faction-points-per-kill"
      label="Extra Faction Points Per Kill"
      value={state.faction_points_per_kill}
      on_change={(value) => onChange('faction_points_per_kill', value)}
      error={errors.faction_points_per_kill}
    />
    <NumberField
      id="guide-quest-xp-reward"
      label="XP Reward"
      value={state.xp_reward}
      on_change={(value) => onChange('xp_reward', value)}
      error={errors.xp_reward}
    />
    <NumberField
      id="guide-quest-gold-reward"
      label="Gold Reward"
      value={state.gold_reward}
      on_change={(value) => onChange('gold_reward', value)}
      error={errors.gold_reward}
    />
    <NumberField
      id="guide-quest-gold-dust-reward"
      label="Gold Dust Rewards"
      value={state.gold_dust_reward}
      on_change={(value) => onChange('gold_dust_reward', value)}
      error={errors.gold_dust_reward}
    />
    <NumberField
      id="guide-quest-shards-reward"
      label="Shards Reward"
      value={state.shards_reward}
      on_change={(value) => onChange('shards_reward', value)}
      error={errors.shards_reward}
    />
  </div>
);

export default ManageGuideQuestsRewardsAndBonuses;
