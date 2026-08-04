import React, { ReactNode } from 'react';

import HolyOilCostSummaryProps from './types/holy-oil-cost-summary-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const HolyOilCostSummary = ({
  currentStacks,
  maximumStacks,
  goldDustCost,
}: HolyOilCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
    <dt>Holy stacks applied</dt>
    <dd>{currentStacks}</dd>

    <dt>Maximum Holy stacks</dt>
    <dd>{maximumStacks}</dd>

    {goldDustCost !== null && (
      <>
        <dt>Gold Dust cost</dt>
        <dd>{formatNumberWithCommas(goldDustCost)}</dd>
      </>
    )}
  </dl>
);

export default HolyOilCostSummary;
