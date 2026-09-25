import { parse } from 'date-fns';

import MarketHistoryChartPointDefinition from '../../item/definitions/market-history-chart-point-definition';
import MarketHistoryRowDefinition from '../../item/definitions/market-history-row-definition';

export const buildMarketHistoryChartPoints = (
  rows: MarketHistoryRowDefinition[]
): MarketHistoryChartPointDefinition[] =>
  rows
    .map((row) => ({
      soldWhenTimestamp: parse(
        row.sold_when,
        'yyyy-MM-dd HH:mm:ss',
        new Date()
      ).getTime(),
      cost: row.cost,
      affixName: row.affix_name,
    }))
    .sort(
      (firstPoint, secondPoint) =>
        firstPoint.soldWhenTimestamp - secondPoint.soldWhenTimestamp
    );
