export interface MonitoringChartPoint {
  period: string;
}

export interface MonitoringChartSeries {
  key: string;
  label: string;
  color: string;
  dash?: string;
}

export default interface MonitoringStatusChartProps<
  ChartPoint extends MonitoringChartPoint,
> {
  title: string;
  description: string;
  points: ChartPoint[];
  series: MonitoringChartSeries[];
}
