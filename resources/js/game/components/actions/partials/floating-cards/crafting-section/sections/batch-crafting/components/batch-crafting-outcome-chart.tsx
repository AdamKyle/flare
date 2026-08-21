import React, { ReactNode, useMemo } from 'react';

import BatchCraftingChartDataPointDefinition from './definitions/batch-crafting-chart-data-point-definition';
import BatchCraftingOutcomeChartProps from './types/batch-crafting-outcome-chart-props';
import { buildBatchCraftingChartData } from '../utils/build-batch-crafting-chart-data';
import { formatBatchCraftingChartTime } from '../utils/format-batch-crafting-duration';

import { formatNumberWithCommas } from 'game-utils/format-number';

import LineChartLineDefinition from 'ui/charts/line-chart/definitions/line-chart-line-definition';
import LineChartYAxisDefinition from 'ui/charts/line-chart/definitions/line-chart-y-axis-definition';
import LineChartColor from 'ui/charts/line-chart/enums/line-chart-color';
import LineChartXAxisType from 'ui/charts/line-chart/enums/line-chart-x-axis-type';
import LineChartYAxisType from 'ui/charts/line-chart/enums/line-chart-y-axis-type';
import LineChart from 'ui/charts/line-chart/line-chart';

const Y_AXES: LineChartYAxisDefinition[] = [
  {
    key: 'counts',
    type: LineChartYAxisType.COUNT,
    visible: false,
    start_at_zero: true,
  },
  {
    key: 'gold',
    type: LineChartYAxisType.NUMBER,
    visible: false,
    start_at_zero: true,
    value_formatter: formatNumberWithCommas,
  },
];

const buildAccessibilityLabel = (hasGoldGained: boolean): string => {
  if (hasGoldGained) {
    return 'Crafting activity line chart showing successful crafts, failed attempts, cumulative Gold spent, and Gold gained.';
  }

  return 'Crafting activity line chart showing successful crafts, failed attempts, and cumulative Gold spent.';
};

const BatchCraftingOutcomeChart = ({
  chart_points,
  processing_started_at,
}: BatchCraftingOutcomeChartProps): ReactNode => {
  const chartData = useMemo(
    () => buildBatchCraftingChartData(processing_started_at, chart_points),
    [processing_started_at, chart_points]
  );

  const hasGoldGained = useMemo(
    () => chart_points.some((point) => point.gold_gained > 0),
    [chart_points]
  );

  const lines = useMemo((): Array<
    LineChartLineDefinition<BatchCraftingChartDataPointDefinition>
  > => {
    const baseLines: Array<
      LineChartLineDefinition<BatchCraftingChartDataPointDefinition>
    > = [
      {
        data_key: 'successful',
        label: 'Successful',
        color: LineChartColor.EMERALD,
        y_axis_key: 'counts',
      },
      {
        data_key: 'failed',
        label: 'Failed',
        color: LineChartColor.ROSE,
        y_axis_key: 'counts',
      },
      {
        data_key: 'gold_spent',
        label: 'Gold Spent',
        color: LineChartColor.REGENT_ST_BLUE,
        y_axis_key: 'gold',
        value_formatter: formatNumberWithCommas,
      },
    ];

    if (!hasGoldGained) {
      return baseLines;
    }

    return [
      ...baseLines,
      {
        data_key: 'gold_gained',
        label: 'Gold Gained',
        color: LineChartColor.MARIGOLD,
        y_axis_key: 'gold',
        value_formatter: formatNumberWithCommas,
      },
    ];
  }, [hasGoldGained]);

  return (
    <LineChart<BatchCraftingChartDataPointDefinition>
      data={chartData}
      x_data_key="elapsed_seconds"
      x_label="Elapsed Time"
      x_axis_type={LineChartXAxisType.NUMBER}
      x_formatter={formatBatchCraftingChartTime}
      y_axes={Y_AXES}
      lines={lines}
      accessibility_label={buildAccessibilityLabel(hasGoldGained)}
      empty_state={
        <p className="text-sm text-gray-600 italic dark:text-gray-400">
          Crafting activity will appear when Batch Crafting begins.
        </p>
      }
    />
  );
};

export default BatchCraftingOutcomeChart;
