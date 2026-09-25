import React, { ReactNode } from 'react';

import QueenCostSummaryProps from './types/queen-cost-summary-props';
import CurrencyDisplay from '../../../../../../../../reusable-components/currency/currency-display';
import { CurrencyDisplayMode } from '../../../../../../../../reusable-components/currency/enums/currency-display-mode';
import { CurrencyType } from '../../../../../../../../reusable-components/currency/enums/currency-type';

const QueenCostSummary = ({
  goldDust,
  shards,
}: QueenCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
    <dt>Gold Dust</dt>
    <dd>
      <CurrencyDisplay
        currency={CurrencyType.GOLD_DUST}
        amount={goldDust}
        display_mode={CurrencyDisplayMode.EXACT}
        show_label={false}
      />
    </dd>
    <dt>Shards</dt>
    <dd>
      <CurrencyDisplay
        currency={CurrencyType.SHARDS}
        amount={shards}
        display_mode={CurrencyDisplayMode.EXACT}
        show_label={false}
      />
    </dd>
  </dl>
);

export default QueenCostSummary;
