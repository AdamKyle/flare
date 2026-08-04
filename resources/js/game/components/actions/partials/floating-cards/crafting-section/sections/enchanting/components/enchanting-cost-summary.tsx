import React, { ReactNode } from 'react';

import EnchantingCostSummaryProps from './types/enchanting-cost-summary-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const EnchantingCostSummary = ({
  totalCost,
}: EnchantingCostSummaryProps): ReactNode => (
  <p className="rounded-md border border-gray-300 p-3 dark:border-gray-700">
    Selected enchantment cost:{' '}
    <strong>{formatNumberWithCommas(totalCost)} Gold</strong>
  </p>
);

export default EnchantingCostSummary;
