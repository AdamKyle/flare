import React, { ReactNode } from 'react';

import { InventoryItemTypes } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import ReadOnlyItemCard from '../../../components/side-peeks/components/items/read-only-item-card';
import FactualLink from '../../quest-item/partials/factual-link';
import QuestDetailProps from '../types/quest-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const QuestRewardsSection = ({
  quest,
  navigation,
  quest_item_ownership: questItemOwnership,
}: QuestDetailProps): ReactNode => {
  const { rewards } = quest;

  const isRewardQuestItem = rewards.item?.type === InventoryItemTypes.QUEST;

  const renderRewardItem = (): ReactNode => {
    if (!rewards.item) {
      return null;
    }

    if (isRewardQuestItem) {
      return (
        <ReadOnlyItemCard
          item_id={rewards.item.item_id}
          name={rewards.item.name}
          description={rewards.item.description}
          effect={rewards.item.effect}
          usable={rewards.item.usable}
          ownership_state={questItemOwnership?.[rewards.item.item_id]}
          on_click={navigation?.on_open_item}
        />
      );
    }

    return (
      <FactualLink
        id={rewards.item.item_id}
        label={rewards.item.name}
        on_click={navigation?.on_open_item}
      />
    );
  };

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

  const fieldLabelClassName =
    'text-glacier-600 dark:text-glacier-400 text-xs font-semibold tracking-wide uppercase';

  const hasSimpleRows =
    (Boolean(rewards.item) && !isRewardQuestItem) ||
    currencyRows.length > 0 ||
    Boolean(rewards.skill) ||
    Boolean(rewards.feature) ||
    Boolean(rewards.passive);

  return (
    <div className="flex flex-col gap-4">
      <h3 className="text-glacier-900 dark:text-glacier-100 text-sm font-semibold">
        Rewards
      </h3>
      {rewards.item && isRewardQuestItem && (
        <div>
          <p className={fieldLabelClassName}>Item</p>
          <div className="mt-1">{renderRewardItem()}</div>
        </div>
      )}
      {hasSimpleRows && (
        <Dl>
          {rewards.item && !isRewardQuestItem && (
            <>
              <Dt>Item</Dt>
              <Dd>{renderRewardItem()}</Dd>
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
      )}
    </div>
  );
};

export default QuestRewardsSection;
