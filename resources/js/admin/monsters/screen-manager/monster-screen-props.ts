import { MonsterScreens } from './monster-screen-constants';

export type MonsterListScreenProps = Record<string, never>;

export interface MonsterShowScreenProps {
  monster_id: number;
}

export interface MonsterFormScreenProps {
  monster_id: number | null;
}

export interface MonsterScreenPropsMap {
  [MonsterScreens.LIST]: MonsterListScreenProps;
  [MonsterScreens.SHOW]: MonsterShowScreenProps;
  [MonsterScreens.FORM]: MonsterFormScreenProps;
}

export type MonsterScreenName = keyof MonsterScreenPropsMap;
