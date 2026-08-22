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

type ResourceLineKey =
  | 'gold_spent'
  | 'gold_gained'
  | 'gold_dust_spent'
  | 'shards_spent'
  | 'copper_coins_spent';

const RESOURCE_LINE_DEFINITIONS: Array<{
  data_key: ResourceLineKey;
  label: string;
  color: LineChartColor;
}> = [
  {
    data_key: 'gold_spent',
    label: 'Gold Spent',
    color: LineChartColor.REGENT_ST_BLUE,
  },
  {
    data_key: 'gold_gained',
    label: 'Gold Gained',
    color: LineChartColor.MARIGOLD,
  },
  {
    data_key: 'gold_dust_spent',
    label: 'Gold Dust Spent',
    color: LineChartColor.REGENT_ST_BLUE,
  },
  {
    data_key: 'shards_spent',
    label: 'Shards Spent',
    color: LineChartColor.MARIGOLD,
  },
  {
    data_key: 'copper_coins_spent',
    label: 'Copper Coins Spent',
    color: LineChartColor.DANUBE,
  },
];

const buildAccessibilityLabel = (resourceLabels: string[]): string => {
  if (resourceLabels.length === 0) {
    return 'Crafting activity line chart showing successful crafts and failed attempts.';
  }

  return `Crafting activity line chart showing successful crafts, failed attempts, and ${resourceLabels.join(', ')}.`;
};

const BatchCraftingOutcomeChart = ({
  chart_points,
  processing_started_at,
}: BatchCraftingOutcomeChartProps): ReactNode => {
  const chartData = useMemo(
    () => buildBatchCraftingChartData(processing_started_at, chart_points),
    [processing_started_at, chart_points]
  );

  const resourceLines = useMemo(() => {
    return RESOURCE_LINE_DEFINITIONS.filter((definition) =>
      chart_points.some((point) => point[definition.data_key] > 0)
    );
  }, [chart_points]);

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
    ];

    const resourceChartLines = resourceLines.map((definition) => ({
      data_key: definition.data_key,
      label: definition.label,
      color: definition.color,
      y_axis_key: 'gold' as const,
      value_formatter: formatNumberWithCommas,
    }));

    return [...baseLines, ...resourceChartLines];
  }, [resourceLines]);

  return (
    <LineChart<BatchCraftingChartDataPointDefinition>
      data={chartData}
      x_data_key="elapsed_seconds"
      x_label="Elapsed Time"
      x_axis_type={LineChartXAxisType.NUMBER}
      x_formatter={formatBatchCraftingChartTime}
      y_axes={Y_AXES}
      lines={lines}
      accessibility_label={buildAccessibilityLabel(
        resourceLines.map((definition) => definition.label)
      )}
      empty_state={
        <p className="text-sm text-gray-600 italic dark:text-gray-400">
          Crafting activity will appear when Batch Crafting begins.
        </p>
      }
    />
  );
};

export default BatchCraftingOutcomeChart;
