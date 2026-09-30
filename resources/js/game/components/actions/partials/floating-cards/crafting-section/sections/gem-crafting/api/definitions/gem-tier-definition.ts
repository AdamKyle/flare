import GemTierCostDefinition from './gem-tier-cost-definition';
import GameGemAbilityDefinition from '../../../../../../../../../api-definitions/gems/game-gem-ability-definition';
import { CharacterGemModifierType } from '../../../../../../../../../reusable-components/character-gem/enums/character-gem-modifier-type';

export interface GemTierPossibleModifierDefinition {
  modifier_type: CharacterGemModifierType;
  minimum: number;
  maximum: number;
  percentage: boolean;
}

export default interface GemTierDefinition {
  min_level: number;
  max_level: number;
  cost: GemTierCostDefinition;
  base_chance: number;
  chance: number;
  possible_modifiers: GemTierPossibleModifierDefinition[];
  possible_abilities: GameGemAbilityDefinition[];
}
