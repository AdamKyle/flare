import { AttackTypes } from '../../enums/attack-types';

export default interface UseManageAttackTypeDetailsVisibilityState {
  showAttackType: boolean;
  attackType: AttackTypes | null;
}
