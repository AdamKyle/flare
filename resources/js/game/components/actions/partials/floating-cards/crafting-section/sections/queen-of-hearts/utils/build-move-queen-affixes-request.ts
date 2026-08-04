import MoveQueenAffixesRequestDefinition from '../api/definitions/move-queen-affixes-request-definition';
import { QueenAffixSelection } from '../enums/queen-affix-selection';

export const buildMoveQueenAffixesRequest = (
  sourceId: number | null,
  destinationId: number | null,
  affix: QueenAffixSelection | null
): MoveQueenAffixesRequestDefinition | null => {
  if (sourceId === null || destinationId === null || affix === null) {
    return null;
  }

  return {
    selected_slot_id: sourceId,
    selected_secondary_slot_id: destinationId,
    selected_affix: affix,
  };
};
