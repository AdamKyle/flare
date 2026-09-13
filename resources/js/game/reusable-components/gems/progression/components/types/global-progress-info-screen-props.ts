import {
  GemFieldProgressionBreakdownDefinition,
  GemProgressionGlobalDefinition,
  GemProgressionScrollDropDefinition,
} from '../../api/definitions/gem-progression-status-definition';

export default interface GlobalProgressInfoScreenProps {
  global: GemProgressionGlobalDefinition;
  scroll_drop: GemProgressionScrollDropDefinition;
  reward_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
  rarity_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
  on_close: () => void;
}
