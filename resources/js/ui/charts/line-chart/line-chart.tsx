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

import LineChartProps from './types/line-chart-props';

const LineChart = <TData extends object>({
  data,
  x_axis_data_key,
  lines,
  tooltip_content,
  responsive_container_props,
  chart_props,
  cartesian_grid_props,
  x_axis_props,
  y_axis_props,
  tooltip_props,
  outer_container_props,
  footer,
  empty_state,
  chart_children,
}: LineChartProps<TData>) => {
  if (data.length === 0) {
    if (!empty_state) {
      return null;
    }

    return <div {...outer_container_props}>{empty_state}</div>;
  }

  return (
    <div {...outer_container_props}>
      <ResponsiveContainer {...responsive_container_props}>
        <RechartsLineChart data={data} {...chart_props}>
          {cartesian_grid_props ? (
            <CartesianGrid {...cartesian_grid_props} />
          ) : null}

          <XAxis dataKey={x_axis_data_key} {...x_axis_props} />
          <YAxis {...y_axis_props} />

          <Tooltip content={tooltip_content} {...tooltip_props} />

          {lines.map((line) => {
            return (
              <Line
                key={line.data_key}
                dataKey={line.data_key}
                {...line.line_props}
              />
            );
          })}

          {chart_children}
        </RechartsLineChart>
      </ResponsiveContainer>

      {footer}
    </div>
  );
};

export default LineChart;
