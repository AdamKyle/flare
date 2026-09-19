import {
  GemFieldProgressionBreakdownDefinition,
  GemProgressionGlobalDefinition,
} from '../../api/definitions/gem-progression-status-definition';

export default interface GlobalProgressInfoScreenProps {
  global: GemProgressionGlobalDefinition;
  reward_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
  rarity_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
  on_close: () => void;
}
