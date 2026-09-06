import { RaceScreens } from './race-screen-constants';

export type RaceListScreenProps = Record<string, never>;

export interface RaceShowScreenProps {
  race_id: number;
}

export interface RaceFormScreenProps {
  race_id: number | null;
}

export interface RaceScreenPropsMap {
  [RaceScreens.LIST]: RaceListScreenProps;
  [RaceScreens.SHOW]: RaceShowScreenProps;
  [RaceScreens.FORM]: RaceFormScreenProps;
}

export type RaceScreenName = keyof RaceScreenPropsMap;
