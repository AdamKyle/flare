import ExplorationChartPointDefinition from './exploration-chart-point-definition';
import ExplorationDamageDefinition from './exploration-damage-definition';

export default interface ExplorationCombatSummarySectionProps {
  chart_points: ExplorationChartPointDefinition[];
  damage: ExplorationDamageDefinition;
  healing: number;
  blocked: number;
}
