import DelveChartPointDefinition from '../../types/delve-chart-point-definition';
import DelveDamageDefinition from '../../types/delve-damage-definition';

export default interface DelveCombatSummarySectionProps {
  chart_points: DelveChartPointDefinition[];
  damage: DelveDamageDefinition;
  healing: number;
  blocked: number;
}
