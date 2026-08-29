import { NpcType } from '../../enums/npc-type';

export default interface NpcListDefinition {
  id: number;
  real_name: string;
  type: NpcType;
  map_name: string | null;
  x_position: number;
  y_position: number;
}
