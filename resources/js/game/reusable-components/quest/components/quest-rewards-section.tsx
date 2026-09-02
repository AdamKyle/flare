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

  const currencyRows: { label: string; value: number }[] = [
    { label: 'Gold', value: rewards.gold ?? 0 },
    { label: 'Gold Dust', value: rewards.gold_dust ?? 0 },
    { label: 'Shards', value: rewards.shards ?? 0 },
    { label: 'XP', value: rewards.xp ?? 0 },
  ].filter((row) => row.value !== 0);

  const hasRows =
    Boolean(rewards.item) ||
    currencyRows.length > 0 ||
    Boolean(rewards.skill) ||
    Boolean(rewards.feature) ||
    Boolean(rewards.passive);

  if (!hasRows) {
    return null;
  }

  return (
    <div>
      <h3 className="text-glacier-900 dark:text-glacier-100 mb-2 text-sm font-semibold">
        Rewards
      </h3>
      <Dl>
        {rewards.item && (
          <>
            <Dt>Item</Dt>
            <Dd>
              <FactualLink
                id={rewards.item.item_id}
                label={rewards.item.name}
                on_click={navigation?.on_open_item}
              />
            </Dd>
          </>
        )}
        {currencyRows.map((row) => (
          <React.Fragment key={row.label}>
            <Dt>{row.label}</Dt>
            <Dd>{row.value}</Dd>
          </React.Fragment>
        ))}
        {rewards.skill && (
          <>
            <Dt>Unlocks Skill</Dt>
            <Dd>{rewards.skill.name}</Dd>
          </>
        )}
        {rewards.feature && (
          <>
            <Dt>Unlocks Feature</Dt>
            <Dd>{rewards.feature}</Dd>
          </>
        )}
        {rewards.passive && (
          <>
            <Dt>Unlocks Passive</Dt>
            <Dd>
              <FactualLink
                id={rewards.passive.id}
                label={rewards.passive.name}
                on_click={navigation?.on_open_passive}
              />
            </Dd>
          </>
        )}
      </Dl>
    </div>
  );
};

export default QuestRewardsSection;
