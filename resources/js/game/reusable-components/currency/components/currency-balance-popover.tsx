import clsx from 'clsx';
import React, { ReactNode } from 'react';

import CurrencyBalancePopoverProps from './types/currency-balance-popover-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const CurrencyBalancePopover = ({
  presentation,
  amount,
  limit,
}: CurrencyBalancePopoverProps): ReactNode => {
  const renderLimit = (): ReactNode => {
    if (limit === null) {
      return null;
    }

    return (
      <p className="text-[10px] text-gray-600 dark:text-gray-400">
        You can have a max of: {formatNumberWithCommas(limit)}
      </p>
    );
  };

  return (
    <div className="space-y-2">
      <p
        className={clsx(
          'flex items-center gap-2 text-base font-semibold',
          presentation.color_class
        )}
      >
        <i className={presentation.icon_class} aria-hidden="true" />
        <span>{presentation.label}</span>
      </p>
      <hr className="border-gray-200 dark:border-gray-700" />
      <div className="space-y-1 text-[10px] text-gray-600 dark:text-gray-400">
        <p>{presentation.obtained_from}</p>
        <p>{presentation.used_for}</p>
      </div>
      {renderLimit()}
      <p className="text-sm font-semibold">
        You Have:{' '}
        <span className={presentation.color_class}>
          {formatNumberWithCommas(amount)} {presentation.label}
        </span>
      </p>
    </div>
  );
};

export default CurrencyBalancePopover;
