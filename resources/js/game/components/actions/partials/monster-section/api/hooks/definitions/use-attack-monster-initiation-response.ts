import AttackMessageDefinition from '../../../../../components/fight-section/deffinitions/attack-message-definition';
import HealthDefinition from '../../definitions/health-definition';

export default interface UseAttackMonsterInitiationResponse {
  health: HealthDefinition;
  attack_messages: AttackMessageDefinition[];
  monster_id: number;
}
