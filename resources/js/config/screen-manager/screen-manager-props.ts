import { Screens } from './screen-manager-constants';
import CharacterSheetProps from '../../game/components/character-sheet/types/character-sheet-props';

export interface AppScreenPropsMap {
  [Screens.CHARACTER_SHEET]: CharacterSheetProps;
}

export type AppScreenName = keyof AppScreenPropsMap;
export type AppScreenPropsOf<K extends AppScreenName> = AppScreenPropsMap[K];

export type ScreenName = AppScreenName;
export type ScreenPropsOf<K extends ScreenName> = AppScreenPropsOf<K>;
