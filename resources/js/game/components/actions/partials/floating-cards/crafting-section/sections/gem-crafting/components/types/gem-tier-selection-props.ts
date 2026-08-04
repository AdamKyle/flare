import GemTierDefinition from '../../api/definitions/gem-tier-definition';

export default interface GemTierSelectionProps {
  tiers: GemTierDefinition[];
  selectedTier: number | null;
  onSelect: (tier: number) => void;
}
