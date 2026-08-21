import type { ReactNode } from 'react';

import LineChartLineDefinition from 'ui/charts/line-chart/definitions/line-chart-line-definition';
import LineChartYAxisDefinition from 'ui/charts/line-chart/definitions/line-chart-y-axis-definition';
import LineChartXAxisType from 'ui/charts/line-chart/enums/line-chart-x-axis-type';

export default interface LineChartProps<TData extends object> {
  data: TData[];
  x_data_key: Extract<keyof TData, string>;
  x_label: string;
  x_axis_type: LineChartXAxisType;
  x_formatter: (value: number) => string;
  lines: Array<LineChartLineDefinition<TData>>;
  y_axes: LineChartYAxisDefinition[];
  accessibility_label: string;
  show_legend?: boolean;
  empty_state?: ReactNode;
  footer?: ReactNode;
}
