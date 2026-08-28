export default interface LogSummaryDefinition {
  total: number;
  by_severity: Record<string, number>;
  chart: Array<{ period: string; count: number }>;
}
