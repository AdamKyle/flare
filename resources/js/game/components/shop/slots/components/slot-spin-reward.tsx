import React, { ReactNode } from 'react';

import SlotSpinRewardProps from './types/slot-spin-reward-props';
import CurrencyDisplay from '../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../reusable-components/currency/enums/currency-display-mode';

const SlotSpinReward = ({ reward }: SlotSpinRewardProps): ReactNode => {
  const renderGain = (): ReactNode => {
    if (reward.amount <= 0) {
      return null;
    }

    return (
      <p className="flex flex-wrap items-center justify-center gap-1 font-semibold">
        <span aria-hidden="true">+</span>
        <span className="sr-only">Gained</span>
        <CurrencyDisplay
          currency={reward.currency}
          amount={reward.amount}
          display_mode={CurrencyDisplayMode.EXACT}
        />
      </p>
    );
  };

  return (
    <div className="space-y-1 text-center text-sm">
      {renderGain()}
      <p className="flex flex-wrap items-center justify-center gap-1">
        <span>You Have:</span>
        <CurrencyDisplay
          currency={reward.currency}
          amount={reward.balance}
          display_mode={CurrencyDisplayMode.BALANCE}
        />
      </p>
    </div>
  );
};

export default SlotSpinReward;
