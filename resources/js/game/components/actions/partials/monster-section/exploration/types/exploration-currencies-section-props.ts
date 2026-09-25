import ExplorationChartPointDefinition from './exploration-chart-point-definition';

export default interface ExplorationCurrenciesSectionProps {
  chart_points: ExplorationChartPointDefinition[];
  currencies: Record<string, number>;
}
