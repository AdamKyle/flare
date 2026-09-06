import { ClassScreens } from './class-screen-constants';

export type ClassListScreenProps = Record<string, never>;

export interface ClassShowScreenProps {
  class_id: number;
}

export interface ClassFormScreenProps {
  class_id: number | null;
}

export interface ClassScreenPropsMap {
  [ClassScreens.LIST]: ClassListScreenProps;
  [ClassScreens.SHOW]: ClassShowScreenProps;
  [ClassScreens.FORM]: ClassFormScreenProps;
}

export type ClassScreenName = keyof ClassScreenPropsMap;
