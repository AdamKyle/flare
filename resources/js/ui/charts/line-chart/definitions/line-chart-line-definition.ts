import LineChartColor from 'ui/charts/line-chart/enums/line-chart-color';

export default interface LineChartLineDefinition<TData extends object> {
  data_key: Extract<keyof TData, string>;
  label: string;
  color: LineChartColor;
  y_axis_key: string;
  value_formatter?: (value: number) => string;
  show_points?: boolean;
}
