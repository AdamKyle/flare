import React, { ReactNode } from 'react';

import HolyOilCostSummaryProps from './types/holy-oil-cost-summary-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

const HolyOilCostSummary = ({
  currentStacks,
  resultingStacks,
  maximumStacks,
  goldDustCost,
  statBonusRange,
  devoidanceRange,
}: HolyOilCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
    <dt>Current Holy stacks</dt>
    <dd>{currentStacks}</dd>

    <dt>Resulting Holy stacks</dt>
    <dd>{resultingStacks}</dd>

    <dt>Maximum Holy stacks</dt>
    <dd>{maximumStacks}</dd>

    {goldDustCost !== null && (
      <>
        <dt>Gold Dust cost</dt>
        <dd>
          <CurrencyDisplay
            currency={CurrencyType.GOLD_DUST}
            amount={goldDustCost}
            display_mode={CurrencyDisplayMode.EXACT}
            show_label={false}
          />
        </dd>
      </>
    )}

    {statBonusRange && (
      <>
        <dt>Possible stat bonus</dt>
        <dd>
          {statBonusRange.min}–{statBonusRange.max}
        </dd>
      </>
    )}

    {devoidanceRange && (
      <>
        <dt>Possible devoidance</dt>
        <dd>
          {devoidanceRange.min}–{devoidanceRange.max}
        </dd>
      </>
    )}
  </dl>
);

export default HolyOilCostSummary;
