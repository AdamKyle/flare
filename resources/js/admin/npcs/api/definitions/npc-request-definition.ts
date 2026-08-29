import { NpcType } from '../../enums/npc-type';

export default interface NpcRequestDefinition {
  real_name: string;
  type: NpcType;
  x_position: number;
  y_position: number;
}
