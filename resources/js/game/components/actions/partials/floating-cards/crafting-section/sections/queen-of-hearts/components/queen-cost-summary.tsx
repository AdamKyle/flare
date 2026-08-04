import React, { ReactNode } from 'react';

import QueenCostSummaryProps from './types/queen-cost-summary-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const QueenCostSummary = ({
  goldDust,
  shards,
}: QueenCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
    <dt>Gold Dust</dt>
    <dd>{formatNumberWithCommas(goldDust)}</dd>
    <dt>Shards</dt>
    <dd>{formatNumberWithCommas(shards)}</dd>
  </dl>
);

export default QueenCostSummary;
