import { NpcType } from '../../enums/npc-type';

export default interface NpcFormOptionsDefinition {
  game_map: {
    id: number;
    name: string;
  };
  npc_types: NpcType[];
  coordinates: {
    x: number[];
    y: number[];
  };
}
