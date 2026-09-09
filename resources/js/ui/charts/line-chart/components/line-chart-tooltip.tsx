import React from 'react';
import { useActiveTooltipDataPoints, useActiveTooltipLabel } from 'recharts';

import LineChartTooltipProps from '../types/line-chart-tooltip-props';
import { resolveLineChartNumber } from '../utils/resolve-line-chart-number';

import LINE_CHART_COLOR_STYLES from 'ui/charts/line-chart/styles/line-chart-color-styles';

const UNAVAILABLE_VALUE_LABEL = 'Unavailable';

const resolveTooltipXValue = (
  activeLabel: unknown,
  rawXValue: unknown
): number | null => {
  const resolvedActiveLabel = resolveLineChartNumber(activeLabel);

  if (resolvedActiveLabel !== null) {
    return resolvedActiveLabel;
  }

  const resolvedRawXValue = resolveLineChartNumber(rawXValue);

  if (resolvedRawXValue !== null) {
    return resolvedRawXValue;
  }

  return null;
};

const LineChartTooltip = <TData extends object>({
  x_data_key,
  x_formatter,
  lines,
}: LineChartTooltipProps<TData>) => {
  const activeDataPoints = useActiveTooltipDataPoints<TData>();
  const activeLabel = useActiveTooltipLabel();

  if (!activeDataPoints?.length) {
    return null;
  }

  const activePoint = activeDataPoints[0];

  if (!activePoint) {
    return null;
  }

  const resolvedXValue = resolveTooltipXValue(
    activeLabel,
    activePoint[x_data_key]
  );

  if (resolvedXValue === null) {
    return null;
  }

  const renderSeriesRow = (
    line: LineChartTooltipProps<TData>['lines'][number]
  ) => {
    const resolvedValue = resolveLineChartNumber(activePoint[line.data_key]);
    const formattedValue =
      resolvedValue === null
        ? UNAVAILABLE_VALUE_LABEL
        : (line.value_formatter?.(resolvedValue) ??
          resolvedValue.toLocaleString());

    return (
      <div key={line.data_key} className="flex items-center gap-1.5">
        <span
          aria-hidden="true"
          className={`h-1.5 w-1.5 rounded-full bg-current ${LINE_CHART_COLOR_STYLES[line.color]}`}
        />
        <span className="text-gray-700 dark:text-gray-300">{line.label}:</span>
        <span className="font-medium text-gray-900 dark:text-gray-100">
          {formattedValue}
        </span>
      </div>
    );
  };

  return (
    <div
      role="tooltip"
      className="space-y-1 rounded-sm bg-gray-100 p-2 text-xs shadow-sm dark:bg-gray-800"
    >
      <div className="font-medium text-gray-900 dark:text-gray-100">
        {x_formatter(resolvedXValue)}
      </div>

      {lines.map(renderSeriesRow)}
    </div>
  );
};

export default LineChartTooltip;
