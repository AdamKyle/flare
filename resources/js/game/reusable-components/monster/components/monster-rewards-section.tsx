import React, { ReactNode } from 'react';

import {
  formatNumberWithCommas,
  formatPercent,
} from '../../../util/format-number';
import MonsterDetailProps from '../types/monster-detail-props';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const MonsterRewardsSection = ({ monster }: MonsterDetailProps): ReactNode => {
  const { identity } = monster;

  const hasRows =
    identity.xp > 0 || identity.gold > 0 || identity.drop_check > 0;

  if (!hasRows) {
    return null;
  }

  return (
    <div>
      <h2 className="text-marigold-700 dark:text-marigold-500 mb-2 text-base font-semibold">
        Rewards
      </h2>
      <Dl>
        {identity.xp > 0 && (
          <>
            <Dt>XP</Dt>
            <Dd>{formatNumberWithCommas(identity.xp)}</Dd>
          </>
        )}
        {identity.gold > 0 && (
          <>
            <Dt>Gold</Dt>
            <Dd>{formatNumberWithCommas(identity.gold)}</Dd>
          </>
        )}
        {identity.drop_check > 0 && (
          <>
            <Dt>Drop Check</Dt>
            <Dd>{formatPercent(identity.drop_check)}</Dd>
          </>
        )}
      </Dl>
    </div>
  );
};

export default MonsterRewardsSection;
