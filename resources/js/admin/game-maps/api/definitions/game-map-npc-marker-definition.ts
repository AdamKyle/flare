import { NpcType } from '../../../npcs/enums/npc-type';

export default interface GameMapNpcMarkerDefinition {
  id: number;
  real_name: string;
  type: NpcType;
  x_position: number;
  y_position: number;
}
