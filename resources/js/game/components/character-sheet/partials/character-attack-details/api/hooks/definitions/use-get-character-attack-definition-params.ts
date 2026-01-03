import { AttackTypes } from '../../../../../enums/attack-types';

export default interface UseGetCharacterAttackDefinitionParams {
  character_id: number;
  attack_type: AttackTypes | null;
}
