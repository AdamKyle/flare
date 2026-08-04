import React, { ReactNode } from 'react';

import TrinketCostSummaryProps from './types/trinket-cost-summary-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const TrinketCostSummary = ({ item }: TrinketCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
    <dt>Gold Dust</dt>
    <dd>{formatNumberWithCommas(item.gold_dust_cost)}</dd>
    <dt>Copper Coins</dt>
    <dd>{formatNumberWithCommas(item.copper_coin_cost)}</dd>
    <dt>Required skill level</dt>
    <dd>{item.skill_level_required}</dd>
  </dl>
);

export default TrinketCostSummary;
