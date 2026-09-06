import { LocationGemScreens } from './location-gem-screen-constants';

export type LocationGemListScreenProps = Record<string, never>;

export interface LocationGemShowScreenProps {
  location_gem_id: number;
}

export interface LocationGemFormScreenProps {
  location_gem_id: number | null;
}

export interface LocationGemScreenPropsMap {
  [LocationGemScreens.LIST]: LocationGemListScreenProps;
  [LocationGemScreens.SHOW]: LocationGemShowScreenProps;
  [LocationGemScreens.FORM]: LocationGemFormScreenProps;
}

export type LocationGemScreenName = keyof LocationGemScreenPropsMap;
