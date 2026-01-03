import { AttackTypes } from '../../../enums/attack-types';

export default interface CharacterAttackTypeBreakdownProps {
  close_attack_details: () => void;
  attack_type: AttackTypes | null;
}
