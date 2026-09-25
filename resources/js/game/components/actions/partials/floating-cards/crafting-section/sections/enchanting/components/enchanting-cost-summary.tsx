import React, { ReactNode } from 'react';

import EnchantingCostSummaryProps from './types/enchanting-cost-summary-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

const EnchantingCostSummary = ({
  totalCost,
}: EnchantingCostSummaryProps): ReactNode => (
  <p className="flex flex-wrap items-center gap-1 rounded-md border border-gray-300 p-3 dark:border-gray-700">
    <span>Selected enchantment cost:</span>
    <CurrencyDisplay
      currency={CurrencyType.GOLD}
      amount={totalCost}
      display_mode={CurrencyDisplayMode.EXACT}
      additional_css="font-bold"
    />
  </p>
);

export default EnchantingCostSummary;
