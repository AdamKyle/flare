import React from 'react';

import MonsterSectionProps from '../types/partials/monster-section-props';

import {
  formatNumberWithCommas,
  formatPercent,
} from 'game-utils/format-number';
import { isNilOrZeroValue } from 'game-utils/general-util';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';
import Separator from 'ui/separator/separator';

const MonsterRewardsSection = (props: MonsterSectionProps) => {
  const { monster } = props;

  const renderXp = () => {
    if (isNilOrZeroValue(monster.xp)) {
      return null;
    }

    return (
      <>
        <Dt>XP:</Dt>
        <Dd>{formatNumberWithCommas(monster.xp)}</Dd>
      </>
    );
  };

  const renderGold = () => {
    if (isNilOrZeroValue(monster.gold)) {
      return null;
    }

    return (
      <>
        <Dt>Gold:</Dt>
        <Dd>{formatNumberWithCommas(monster.gold)}</Dd>
      </>
    );
  };

  const renderShardReward = () => {
    if (isNilOrZeroValue(monster.shard_reward)) {
      return null;
    }

    return (
      <>
        <Dt>Shard Reward:</Dt>
        <Dd>{formatNumberWithCommas(monster.shard_reward)}</Dd>
      </>
    );
  };

  const renderDropChance = () => {
    if (isNilOrZeroValue(monster.drop_chance)) {
      return null;
    }

    return (
      <>
        <Dt>Drop Chance:</Dt>
        <Dd>{formatPercent(monster.drop_chance)}</Dd>
      </>
    );
  };

  return (
    <>
      <h3 className="text-mango-tango-500 dark:text-mango-tango-300 mt-5">
        Rewards
      </h3>
      <Separator />
      <Dl>
        {renderXp()}
        {renderGold()}
        {renderShardReward()}
        {renderDropChance()}
      </Dl>
    </>
  );
};

export default MonsterRewardsSection;
