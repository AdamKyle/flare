import LineChartLineDefinition from 'ui/charts/line-chart/definitions/line-chart-line-definition';

export default interface LineChartScreenReaderTableProps<TData extends object> {
  data: TData[];
  x_data_key: Extract<keyof TData, string>;
  x_label: string;
  x_formatter: (value: number) => string;
  lines: Array<LineChartLineDefinition<TData>>;
  accessibility_label: string;
}
