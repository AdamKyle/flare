import { NpcType } from '../../enums/npc-type';

export default interface NpcDetailDefinition {
  id: number;
  game_map: { id: number; name: string };
  real_name: string;
  type: NpcType;
  x_position: number;
  y_position: number;
  quest_count: number;
  reward_item_count: number;
}
