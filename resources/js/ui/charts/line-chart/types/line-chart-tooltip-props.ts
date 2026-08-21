import LineChartLineDefinition from 'ui/charts/line-chart/definitions/line-chart-line-definition';

export default interface LineChartTooltipProps<TData extends object> {
  x_data_key: Extract<keyof TData, string>;
  x_formatter: (value: number) => string;
  lines: Array<LineChartLineDefinition<TData>>;
}
