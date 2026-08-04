import React, { ReactNode } from 'react';

import LabyrinthOracleCostSummaryProps from './types/labyrinth-oracle-cost-summary-props';

import { formatNumberWithCommas } from 'game-utils/format-number';

const LabyrinthOracleCostSummary = ({
  costs,
}: LabyrinthOracleCostSummaryProps): ReactNode => (
  <dl className="grid grid-cols-2 gap-2 rounded-md border border-gray-300 p-3 dark:border-gray-700">
    <dt>Gold</dt>
    <dd>{formatNumberWithCommas(costs.gold)}</dd>

    <dt>Shards</dt>
    <dd>{formatNumberWithCommas(costs.shards)}</dd>

    <dt>Gold Dust</dt>
    <dd>{formatNumberWithCommas(costs.gold_dust)}</dd>
  </dl>
);

export default LabyrinthOracleCostSummary;
