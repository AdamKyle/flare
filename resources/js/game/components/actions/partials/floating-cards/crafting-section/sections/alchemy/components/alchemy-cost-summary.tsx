import React, { ReactNode } from 'react';

import AlchemyCostSummaryProps from './types/alchemy-cost-summary-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

import { formatNumberWithCommas } from 'game-utils/format-number';

const AlchemyCostSummary = ({ item }: AlchemyCostSummaryProps): ReactNode => {
  const renderCost = (
    label: string,
    currency: CurrencyType,
    amount: number
  ): ReactNode => {
    if (amount <= 0) {
      return null;
    }

    return (
      <>
        <dt>{label}</dt>
        <dd>
          <CurrencyDisplay
            currency={currency}
            amount={amount}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </dd>
      </>
    );
  };

  return (
    <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
      {renderCost('Gold Dust', CurrencyType.GOLD_DUST, item.gold_dust_cost)}
      {renderCost('Shards', CurrencyType.SHARDS, item.shards_cost)}
      <dt>Owned</dt>
      <dd>{formatNumberWithCommas(item.owned_amount)}</dd>
    </dl>
  );
};

export default AlchemyCostSummary;
