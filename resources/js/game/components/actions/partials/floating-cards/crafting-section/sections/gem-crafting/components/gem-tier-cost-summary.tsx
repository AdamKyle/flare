import React, { ReactNode } from 'react';

import GemTierCostSummaryProps from './types/gem-tier-cost-summary-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const GemTierCostSummary = ({ tier }: GemTierCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
    <dt>Gold Dust</dt>
    <dd>{formatNumberWithCommas(tier.cost.gold_dust)}</dd>
    <dt>Shards</dt>
    <dd>{formatNumberWithCommas(tier.cost.shards)}</dd>
    <dt>Copper Coins</dt>
    <dd>{formatNumberWithCommas(tier.cost.copper_coins)}</dd>
    <dt>Item value range</dt>
    <dd>
      {tier.min}–{tier.max}
    </dd>
    <dt>Skill level range</dt>
    <dd>
      {tier.min_level}–{tier.max_level}
    </dd>
    <dt>Success chance (fraction)</dt>
    <dd>{tier.chance}</dd>
  </dl>
);

export default GemTierCostSummary;
