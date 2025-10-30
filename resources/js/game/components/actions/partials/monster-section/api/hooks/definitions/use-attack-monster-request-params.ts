import { AttackType } from '../../../enums/attack-type';
import { BattleType } from '../../../enums/battle-type';

export default interface UseAttackMonsterRequestParams {
  character_id: number;
  monster_id: number;
  attack_type?: AttackType;
  battle_type?: BattleType;
}
