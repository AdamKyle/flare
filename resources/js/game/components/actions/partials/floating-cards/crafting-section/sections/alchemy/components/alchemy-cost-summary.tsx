import React, { ReactNode } from 'react';

import AlchemyCostSummaryProps from './types/alchemy-cost-summary-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const AlchemyCostSummary = ({ item }: AlchemyCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
    {item.gold_dust_cost > 0 && (
      <>
        <dt>Gold Dust</dt>
        <dd>{formatNumberWithCommas(item.gold_dust_cost)}</dd>
      </>
    )}
    {item.shards_cost > 0 && (
      <>
        <dt>Shards</dt>
        <dd>{formatNumberWithCommas(item.shards_cost)}</dd>
      </>
    )}
    <dt>Owned</dt>
    <dd>{formatNumberWithCommas(item.owned_amount)}</dd>
  </dl>
);

export default AlchemyCostSummary;
