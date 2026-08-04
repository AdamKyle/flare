import QueenCostDefinition from './queen-cost-definition';
import { QueenAffixSelection } from '../../enums/queen-affix-selection';
import { QueenRerollType } from '../../enums/queen-reroll-type';

export type QueenRerollCostsDefinition = Record<
  QueenAffixSelection,
  Partial<Record<QueenRerollType, QueenCostDefinition>>
>;

export type QueenMovementCostsDefinition = Record<
  number,
  Partial<Record<QueenAffixSelection, QueenCostDefinition>>
>;

export default interface QueenCostsDefinition {
  reroll: QueenRerollCostsDefinition;
  movement: QueenMovementCostsDefinition;
}
