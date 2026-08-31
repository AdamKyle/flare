import React, { ReactNode } from 'react';

import FactualLink from '../../quest-item/partials/factual-link';
import QuestDetailProps from '../types/quest-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const QuestRewardsSection = ({
  quest,
  navigation,
}: QuestDetailProps): ReactNode => {
  const { rewards } = quest;

  const renderRewardItem = (): ReactNode => {
    if (!rewards.item) {
      return 'None';
    }

    return (
      <FactualLink
        id={rewards.item.item_id}
        label={rewards.item.name}
        on_click={navigation?.on_open_item}
      />
    );
  };

  const renderPassive = (): ReactNode => {
    if (!rewards.passive) {
      return 'None';
    }

    return (
      <FactualLink
        id={rewards.passive.id}
        label={rewards.passive.name}
        on_click={navigation?.on_open_passive}
      />
    );
  };

  return (
    <div>
      <h3 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Rewards
      </h3>
      <Dl>
        <Dt>Item</Dt>
        <Dd>{renderRewardItem()}</Dd>
        <Dt>Gold</Dt>
        <Dd>{rewards.gold ?? 0}</Dd>
        <Dt>Gold Dust</Dt>
        <Dd>{rewards.gold_dust ?? 0}</Dd>
        <Dt>Shards</Dt>
        <Dd>{rewards.shards ?? 0}</Dd>
        <Dt>XP</Dt>
        <Dd>{rewards.xp ?? 0}</Dd>
        <Dt>Unlocks Skill</Dt>
        <Dd>{rewards.skill ? rewards.skill.name : 'None'}</Dd>
        <Dt>Unlocks Feature</Dt>
        <Dd>{rewards.feature ?? 'None'}</Dd>
        <Dt>Unlocks Passive</Dt>
        <Dd>{renderPassive()}</Dd>
      </Dl>
    </div>
  );
};

export default QuestRewardsSection;
