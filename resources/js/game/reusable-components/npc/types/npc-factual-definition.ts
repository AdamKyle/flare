import { NpcType } from '../enums/npc-type';

export default interface NpcFactualDefinition {
  id: number;
  real_name: string;
  type: NpcType;
  x_position: number;
  y_position: number;
  game_map: { id: number; name: string };
}
