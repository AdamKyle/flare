import { NpcScreens } from './npc-screen-constants';

export type NpcListScreenProps = Record<string, never>;

export interface NpcShowScreenProps {
  npc_id: number;
}

export interface NpcFormScreenEntryProps {
  game_map_id: number | null;
}

export interface NpcScreenPropsMap {
  [NpcScreens.LIST]: NpcListScreenProps;
  [NpcScreens.SHOW]: NpcShowScreenProps;
  [NpcScreens.FORM]: NpcFormScreenEntryProps;
}

export type NpcScreenName = keyof NpcScreenPropsMap;
export type NpcScreenPropsOf<K extends NpcScreenName> = NpcScreenPropsMap[K];
