import React from 'react';
import {
  CartesianGrid,
  Line,
  LineChart as RechartsLineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

import LineChartLegend from './components/line-chart-legend';
import LineChartScreenReaderTable from './components/line-chart-screen-reader-table';
import LineChartTooltip from './components/line-chart-tooltip';
import LineChartLineDefinition from './definitions/line-chart-line-definition';
import LineChartYAxisDefinition from './definitions/line-chart-y-axis-definition';
import LineChartXAxisType from './enums/line-chart-x-axis-type';
import LineChartYAxisType from './enums/line-chart-y-axis-type';
import LineChartProps from './types/line-chart-props';
import { resolveLineChartNumber } from './utils/resolve-line-chart-number';

import LINE_CHART_COLOR_STYLES from 'ui/charts/line-chart/styles/line-chart-color-styles';

const AXIS_TICK_STYLE = { fill: 'currentColor', fontSize: 12 };
const AXIS_LINE_STYLE = { stroke: 'currentColor', opacity: 0.35 };

const buildYAxisTickFormatter = (
  valueFormatter: ((value: number) => string) | undefined
): ((value: unknown) => string) | undefined => {
  if (!valueFormatter) {
    return undefined;
  }

  return (value: unknown) => {
    const resolvedValue = resolveLineChartNumber(value);

    if (resolvedValue === null) {
      return '';
    }

    return valueFormatter(resolvedValue);
  };
};

const formatXAxisTick = (
  value: unknown,
  x_formatter: (value: number) => string
): string => {
  const resolvedValue = resolveLineChartNumber(value);

  if (resolvedValue === null) {
    return '';
  }

  return x_formatter(resolvedValue);
};

const renderYAxis = (yAxis: LineChartYAxisDefinition) => {
  return (
    <YAxis
      key={yAxis.key}
      yAxisId={yAxis.key}
      hide={!yAxis.visible}
      allowDecimals={yAxis.type !== LineChartYAxisType.COUNT}
      domain={yAxis.start_at_zero ? [0, 'auto'] : ['auto', 'auto']}
      width={yAxis.visible ? 56 : 0}
      tick={AXIS_TICK_STYLE}
      tickFormatter={buildYAxisTickFormatter(yAxis.value_formatter)}
      axisLine={AXIS_LINE_STYLE}
      tickLine={AXIS_LINE_STYLE}
    />
  );
};

const renderLine = <TData extends object>(
  line: LineChartLineDefinition<TData>
) => {
  return (
    <Line
      key={line.data_key}
      dataKey={line.data_key}
      yAxisId={line.y_axis_key}
      type="monotone"
      stroke="currentColor"
      strokeWidth={2}
      connectNulls={false}
      className={LINE_CHART_COLOR_STYLES[line.color]}
      dot={line.show_points ? { r: 3, fill: 'currentColor' } : false}
      activeDot={{ r: 4, fill: 'currentColor' }}
    />
  );
};

const LineChart = <TData extends object>({
  data,
  x_data_key,
  x_label,
  x_axis_type,
  x_formatter,
  lines,
  y_axes,
  accessibility_label,
  show_legend = true,
  empty_state,
  footer,
}: LineChartProps<TData>) => {
  if (data.length === 0) {
    if (!empty_state) {
      return null;
    }

    return <div className="w-full">{empty_state}</div>;
  }

  return (
    <figure className="w-full">
      <figcaption className="sr-only">{accessibility_label}</figcaption>

      <div className="h-48 w-full sm:h-56">
        <ResponsiveContainer width="100%" height="100%">
          <RechartsLineChart
            data={data}
            margin={{ top: 8, right: 8, bottom: 8, left: 0 }}
          >
            <CartesianGrid stroke="currentColor" strokeOpacity={0.15} />

            <XAxis
              dataKey={x_data_key}
              type="number"
              scale={x_axis_type === LineChartXAxisType.TIME ? 'time' : 'auto'}
              domain={['dataMin', 'dataMax']}
              minTickGap={24}
              tick={AXIS_TICK_STYLE}
              tickFormatter={(value: unknown) =>
                formatXAxisTick(value, x_formatter)
              }
              axisLine={AXIS_LINE_STYLE}
              tickLine={AXIS_LINE_STYLE}
              label={{
                value: x_label,
                position: 'insideBottom',
                offset: -4,
                fill: 'currentColor',
                fontSize: 12,
              }}
            />

            {y_axes.map(renderYAxis)}

            <Tooltip
              content={
                <LineChartTooltip
                  x_data_key={x_data_key}
                  x_formatter={x_formatter}
                  lines={lines}
                />
              }
              cursor={{ stroke: 'currentColor', strokeOpacity: 0.25 }}
            />

            {lines.map(renderLine)}
          </RechartsLineChart>
        </ResponsiveContainer>
      </div>

      {show_legend ? (
        <div className="mt-2">
          <LineChartLegend lines={lines} />
        </div>
      ) : null}

      <LineChartScreenReaderTable
        data={data}
        x_data_key={x_data_key}
        x_label={x_label}
        x_formatter={x_formatter}
        lines={lines}
        accessibility_label={accessibility_label}
      />

      {footer}
    </figure>
  );
};

export default LineChart;
