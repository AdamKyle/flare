import {
  GemFieldProgressionBreakdownDefinition,
  GemProgressionGlobalDefinition,
  GemProgressionPersonalDefinition,
  GemProgressionScrollDropDefinition,
} from '../../api/definitions/gem-progression-status-definition';

export default interface GemProgressionLevelSectionProps {
  global: GemProgressionGlobalDefinition;
  personal: GemProgressionPersonalDefinition;
  scroll_drop: GemProgressionScrollDropDefinition;
  reward_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
  rarity_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
}
