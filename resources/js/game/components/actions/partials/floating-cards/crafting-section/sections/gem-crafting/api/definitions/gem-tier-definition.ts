import GemTierCostDefinition from './gem-tier-cost-definition';

export default interface GemTierDefinition {
  min_level: number;
  max_level: number;
  cost: GemTierCostDefinition;
  chance: number;
}
