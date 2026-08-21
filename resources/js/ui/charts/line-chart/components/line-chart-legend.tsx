import React from 'react';

import LineChartLegendProps from '../types/line-chart-legend-props';

import LINE_CHART_COLOR_STYLES from 'ui/charts/line-chart/styles/line-chart-color-styles';

const LineChartLegend = <TData extends object>({
  lines,
}: LineChartLegendProps<TData>) => {
  const renderLegendItem = (
    line: LineChartLegendProps<TData>['lines'][number]
  ) => {
    return (
      <li key={line.data_key} className="flex items-center gap-1.5">
        <span
          aria-hidden="true"
          className={`h-2 w-2 rounded-full bg-current ${LINE_CHART_COLOR_STYLES[line.color]}`}
        />
        <span className="text-xs text-gray-700 dark:text-gray-300">
          {line.label}
        </span>
      </li>
    );
  };

  return (
    <ul className="flex flex-wrap items-center gap-x-4 gap-y-1.5">
      {lines.map(renderLegendItem)}
    </ul>
  );
};

export default LineChartLegend;
