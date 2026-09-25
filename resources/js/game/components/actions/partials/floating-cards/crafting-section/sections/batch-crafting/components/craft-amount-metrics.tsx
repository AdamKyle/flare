import React, { ReactNode } from 'react';

import CraftAmountMetricsProps from './types/craft-amount-metrics-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';
import { BatchCraftingDisposition } from '../enums/batch-crafting-disposition';

import { formatNumberWithCommas } from 'game-utils/format-number';

const CraftAmountMetrics = ({
  disposition,
  successful,
  failed,
  remaining,
  gold_spent,
  gold_gained,
  gold_left,
}: CraftAmountMetricsProps): ReactNode => {
  const renderGoldGained = () => {
    if (disposition !== BatchCraftingDisposition.SELL) {
      return null;
    }

    return (
      <div>
        <dt className="text-xs text-gray-600 dark:text-gray-400">
          Gold Gained
        </dt>
        <dd className="font-semibold text-gray-900 dark:text-gray-100">
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={gold_gained}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </dd>
      </div>
    );
  };

  return (
    <dl className="xsm:grid-cols-2 grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-3">
      <div>
        <dt className="text-xs text-gray-600 dark:text-gray-400">Successful</dt>
        <dd className="font-semibold text-emerald-600 dark:text-emerald-400">
          {formatNumberWithCommas(successful)}
        </dd>
      </div>

      <div>
        <dt className="text-xs text-gray-600 dark:text-gray-400">Failed</dt>
        <dd className="font-semibold text-rose-600 dark:text-rose-400">
          {formatNumberWithCommas(failed)}
        </dd>
      </div>

      <div>
        <dt className="text-xs text-gray-600 dark:text-gray-400">Remaining</dt>
        <dd className="font-semibold text-gray-900 dark:text-gray-100">
          {formatNumberWithCommas(remaining ?? 0)}
        </dd>
      </div>

      <div>
        <dt className="text-xs text-gray-600 dark:text-gray-400">Gold Spent</dt>
        <dd className="font-semibold text-gray-900 dark:text-gray-100">
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={gold_spent}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </dd>
      </div>

      {renderGoldGained()}

      <div>
        <dt className="text-xs text-gray-600 dark:text-gray-400">Gold Left</dt>
        <dd className="font-semibold text-gray-900 dark:text-gray-100">
          <CurrencyDisplay
            currency={CurrencyType.GOLD}
            amount={gold_left}
            display_mode={CurrencyDisplayMode.BALANCE}
            show_label={false}
          />
        </dd>
      </div>
    </dl>
  );
};

export default CraftAmountMetrics;
