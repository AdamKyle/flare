import GemTierCostDefinition from './gem-tier-cost-definition';

export default interface GemTierDefinition {
  min: number;
  max: number;
  min_level: number;
  max_level: number;
  cost: GemTierCostDefinition;
  chance: number;
}
