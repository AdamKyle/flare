import { AttackTypes } from '../../enums/attack-types';

export default interface UseManageAttackDetailsBreakdown {
  openAttackDetails: (attackType: AttackTypes) => void;
}
