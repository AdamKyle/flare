import { NpcType } from '../../enums/npc-type';

export default interface NpcDefinition {
  id: number;
  game_map_id: number;
  name: string;
  real_name: string;
  type: NpcType;
  x_position: number;
  y_position: number;
}
