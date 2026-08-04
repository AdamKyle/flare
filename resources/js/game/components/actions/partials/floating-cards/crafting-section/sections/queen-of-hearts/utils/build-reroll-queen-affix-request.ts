import RerollQueenAffixRequestDefinition from '../api/definitions/reroll-queen-affix-request-definition';
import { QueenAffixSelection } from '../enums/queen-affix-selection';
import { QueenRerollType } from '../enums/queen-reroll-type';

export const buildRerollQueenAffixRequest = (
  slotId: number | null,
  affix: QueenAffixSelection | null,
  rerollType: QueenRerollType | null
): RerollQueenAffixRequestDefinition | null => {
  if (slotId === null || affix === null || rerollType === null) {
    return null;
  }

  return {
    selected_slot_id: slotId,
    selected_affix: affix,
    selected_reroll_type: rerollType,
  };
};
