import { AttackMessageType } from '../enums/attack-message-type';

export default interface AttackMessageDefinition {
  message: string;
  type: AttackMessageType;
}
