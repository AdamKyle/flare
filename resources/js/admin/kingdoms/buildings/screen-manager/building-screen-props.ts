import { BuildingScreens } from './building-screen-constants';

export type BuildingListScreenProps = Record<string, never>;

export interface BuildingShowScreenProps {
  building_id: number;
}

export interface BuildingFormScreenProps {
  building_id: number | null;
}

export interface BuildingScreenPropsMap {
  [BuildingScreens.BUILDING_LIST]: BuildingListScreenProps;
  [BuildingScreens.BUILDING_SHOW]: BuildingShowScreenProps;
  [BuildingScreens.BUILDING_FORM]: BuildingFormScreenProps;
}

export type BuildingScreenName = keyof BuildingScreenPropsMap;
