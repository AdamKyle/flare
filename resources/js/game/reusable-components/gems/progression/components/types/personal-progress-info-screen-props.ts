import {
  GemFieldProgressionBreakdownDefinition,
  GemProgressionPersonalDefinition,
  GemProgressionScrollDropDefinition,
} from '../../api/definitions/gem-progression-status-definition';

export default interface PersonalProgressInfoScreenProps {
  personal: GemProgressionPersonalDefinition;
  scroll_drop: GemProgressionScrollDropDefinition;
  reward_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
  rarity_effect_breakdown: GemFieldProgressionBreakdownDefinition[];
  on_close: () => void;
}
