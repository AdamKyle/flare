import DelveChartPointDefinition from '../../types/delve-chart-point-definition';
import DelveTotalsDefinition from '../../types/delve-totals-definition';

export default interface DelveProgressSectionProps {
  chart_points: DelveChartPointDefinition[];
  totals: DelveTotalsDefinition;
}
