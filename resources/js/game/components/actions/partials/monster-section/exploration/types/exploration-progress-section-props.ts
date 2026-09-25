import ExplorationChartPointDefinition from './exploration-chart-point-definition';
import ExplorationTotalsDefinition from './exploration-totals-definition';

export default interface ExplorationProgressSectionProps {
  chart_points: ExplorationChartPointDefinition[];
  totals: ExplorationTotalsDefinition;
}
