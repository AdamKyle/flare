import GameGemAbilityDefinition from './game-gem-ability-definition';
import { CharacterGemModifierType } from '../../reusable-components/character-gem/enums/character-gem-modifier-type';

export default interface CharacterGemModifierDefinition {
  roll_position: number;
  modifier_type: CharacterGemModifierType;
  amount: number | null;
  ability: GameGemAbilityDefinition | null;
}
