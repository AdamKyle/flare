import { LocationScreens } from './location-screen-constants';

export type LocationListScreenProps = Record<string, never>;

export interface LocationShowScreenProps {
  location_id: number;
}

export interface LocationFormScreenEntryProps {
  game_map_id: number | null;
}

export interface LocationScreenPropsMap {
  [LocationScreens.LIST]: LocationListScreenProps;
  [LocationScreens.SHOW]: LocationShowScreenProps;
  [LocationScreens.FORM]: LocationFormScreenEntryProps;
}

export type LocationScreenName = keyof LocationScreenPropsMap;
export type LocationScreenPropsOf<K extends LocationScreenName> =
  LocationScreenPropsMap[K];
