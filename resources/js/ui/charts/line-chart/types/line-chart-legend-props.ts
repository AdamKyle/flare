import LineChartLineDefinition from 'ui/charts/line-chart/definitions/line-chart-line-definition';

export default interface LineChartLegendProps<TData extends object> {
  lines: Array<LineChartLineDefinition<TData>>;
}
