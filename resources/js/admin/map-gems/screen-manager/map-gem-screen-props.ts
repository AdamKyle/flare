import { MapGemScreens } from './map-gem-screen-constants';

export type MapGemListScreenProps = Record<string, never>;

export interface MapGemShowScreenProps {
  map_gem_id: number;
}

export interface MapGemFormScreenProps {
  map_gem_id: number | null;
}

export interface MapGemScreenPropsMap {
  [MapGemScreens.LIST]: MapGemListScreenProps;
  [MapGemScreens.SHOW]: MapGemShowScreenProps;
  [MapGemScreens.FORM]: MapGemFormScreenProps;
}

export type MapGemScreenName = keyof MapGemScreenPropsMap;
