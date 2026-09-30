import GemTierDefinition from '../../api/definitions/gem-tier-definition';

export default interface GemTierCostSummaryProps {
  tier: GemTierDefinition;
  tierNumber: number;
  on_open_details: () => void;
}
