import { formatDistanceToNowStrict } from 'date-fns';
import React from 'react';
import { useActiveTooltipDataPoints, useActiveTooltipLabel } from 'recharts';

import MarketHistoryChartPointDefinition from '../../definitions/market-history-chart-point-definition';

import { formatNumberWithCommas } from 'game-utils/format-number';

const MarketHistoryChartTooltip = () => {
  const activeDataPoints =
    useActiveTooltipDataPoints<MarketHistoryChartPointDefinition>();
  const activeLabel = useActiveTooltipLabel();

  if (!activeDataPoints?.length) {
    return null;
  }

  const firstPoint = activeDataPoints[0];

  if (
    !firstPoint ||
    typeof firstPoint.cost !== 'number' ||
    typeof firstPoint.soldWhenTimestamp !== 'number'
  ) {
    return null;
  }

  const resolvedTimestamp =
    typeof activeLabel === 'number'
      ? activeLabel
      : firstPoint.soldWhenTimestamp;

  return (
    <div
      role="tooltip"
      className="rounded-sm bg-gray-100/90 p-2 text-xs text-gray-900 dark:bg-gray-800/90 dark:text-gray-100"
    >
      <div className="font-medium">
        {formatNumberWithCommas(firstPoint.cost)} gold
      </div>
      <div className="mt-0.5">
        {formatDistanceToNowStrict(new Date(resolvedTimestamp), {
          addSuffix: true,
        })}
      </div>
    </div>
  );
};

export default MarketHistoryChartTooltip;
