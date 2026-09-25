import React, { ReactNode } from 'react';

import CurrencyDisplay from '../../currency/currency-display';
import { CurrencyDisplayMode } from '../../currency/enums/currency-display-mode';
import { CurrencyType } from '../../currency/enums/currency-type';
import MonsterDetailProps from '../types/monster-detail-props';

import {
  formatNumberWithCommas,
  formatPercent,
} from 'game-utils/format-number';

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

  const renderGoldReward = (): ReactNode => {
    if (identity.gold <= 0) {
      return null;
    }

    return (
      <>
        <Dt>Gold</Dt>
        <Dd>
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={identity.gold}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </Dd>
      </>
    );
  };

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
        {renderGoldReward()}
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
