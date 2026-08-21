import LineChartYAxisType from 'ui/charts/line-chart/enums/line-chart-y-axis-type';

export default interface LineChartYAxisDefinition {
  key: string;
  type: LineChartYAxisType;
  visible: boolean;
  start_at_zero: boolean;
  value_formatter?: (value: number) => string;
}
