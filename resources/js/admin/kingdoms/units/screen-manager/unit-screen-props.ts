import { UnitScreens } from './unit-screen-constants';

export type UnitListScreenProps = Record<string, never>;

export interface UnitShowScreenProps {
  unit_id: number;
}

export interface UnitFormScreenProps {
  unit_id: number | null;
}

export interface UnitScreenPropsMap {
  [UnitScreens.UNIT_LIST]: UnitListScreenProps;
  [UnitScreens.UNIT_SHOW]: UnitShowScreenProps;
  [UnitScreens.UNIT_FORM]: UnitFormScreenProps;
}

export type UnitScreenName = keyof UnitScreenPropsMap;
