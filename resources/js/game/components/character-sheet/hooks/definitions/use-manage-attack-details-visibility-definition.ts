import { AttackTypes } from '../../enums/attack-types';

export default interface UseManageAttackDetailsVisibilityDefinition {
  attackType: AttackTypes | null;
  showAttackType: boolean;
  closeAttackDetails: () => void;
}
