import { QueenAffixSelection } from '../../enums/queen-affix-selection';

export default interface MoveQueenAffixesRequestDefinition {
  selected_slot_id: number;
  selected_secondary_slot_id: number;
  selected_affix: QueenAffixSelection;
}
