import { ClassMasteryScreens } from './class-mastery-screen-constants';

export type ClassMasteryListScreenProps = Record<string, never>;

export interface ClassMasteryShowScreenProps {
  class_mastery_id: number;
}

export interface ClassMasteryFormScreenProps {
  class_mastery_id: number | null;
}

export interface ClassMasteryScreenPropsMap {
  [ClassMasteryScreens.LIST]: ClassMasteryListScreenProps;
  [ClassMasteryScreens.SHOW]: ClassMasteryShowScreenProps;
  [ClassMasteryScreens.FORM]: ClassMasteryFormScreenProps;
}

export type ClassMasteryScreenName = keyof ClassMasteryScreenPropsMap;
