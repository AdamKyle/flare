import CharacterGemModifierDefinition from '../../../api-definitions/gems/character-gem-modifier-definition';
import {
  characterGemModifierLabels,
  integerCharacterGemModifierTypes,
} from '../enums/character-gem-modifier-type';
import FormattedCharacterGemModifier from '../types/formatted-character-gem-modifier';

import {
  formatNumberWithCommas,
  formatPercent,
} from 'game-utils/format-number';

export const formatCharacterGemModifier = (
  modifier: CharacterGemModifierDefinition
): FormattedCharacterGemModifier | null => {
  if (modifier.amount === null) {
    return null;
  }

  const formattedAmount = integerCharacterGemModifierTypes.has(
    modifier.modifier_type
  )
    ? formatNumberWithCommas(modifier.amount)
    : formatPercent(modifier.amount);

  return {
    label: characterGemModifierLabels[modifier.modifier_type],
    value: modifier.amount > 0 ? `+${formattedAmount}` : formattedAmount,
  };
};
