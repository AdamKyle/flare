import { QueenAffixSelection } from '../../enums/queen-affix-selection';
import { QueenRerollType } from '../../enums/queen-reroll-type';

export default interface RerollQueenAffixRequestDefinition {
  selected_slot_id: number;
  selected_affix: QueenAffixSelection;
  selected_reroll_type: QueenRerollType;
}
