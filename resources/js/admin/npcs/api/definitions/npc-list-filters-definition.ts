import { NpcType } from '../../enums/npc-type';

export default interface NpcListFiltersDefinition {
  game_map_id: number | null;
  type: NpcType | null;
  [key: string]: number | NpcType | null;
}
