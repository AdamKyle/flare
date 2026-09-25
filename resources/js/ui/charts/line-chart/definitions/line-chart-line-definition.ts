import { ReactNode } from 'react';

import LineChartColor from 'ui/charts/line-chart/enums/line-chart-color';

export default interface LineChartLineDefinition<TData extends object> {
  data_key: Extract<keyof TData, string>;
  label: string;
  color: LineChartColor;
  y_axis_key: string;
  value_formatter?: (value: number) => string;
  value_renderer?: (value: number) => ReactNode;
  show_points?: boolean;
}
