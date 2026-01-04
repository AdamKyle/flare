import { AttackTypes } from '../../../../enums/attack-types';
import CharacterAttackBreakDownDefinition from '../../api/definitions/character-attack-break-down-definition';

export default interface AttackTypesBreakDownProps {
  break_down: CharacterAttackBreakDownDefinition;
  type: AttackTypes;
}
